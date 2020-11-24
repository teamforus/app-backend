<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityStoreValidateEmailRequest;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use App\Traits\ThrottleLoginAttempts;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class IdentityController
 * @package App\Http\Controllers\Api
 */
class IdentityController extends Controller
{
    use ThrottleWithMeta;

    protected $identityRepo;
    protected $mailService;
    protected $recordRepo;

    /**
     * IdentityController constructor.
     *
     * @param IIdentityRepo $identityRepo
     * @param IRecordRepo $recordRepo
     * @param NotificationService $notificationService
     */
    public function __construct(
        IIdentityRepo $identityRepo,
        IRecordRepo $recordRepo,
        NotificationService $notificationService
    ) {
        $this->maxAttempts = env('AUTH_THROTTLE_ATTEMPTS', 10);
        $this->decayMinutes = env('AUTH_THROTTLE_DECAY', 10);

        $this->mailService = $notificationService;
        $this->identityRepo = $identityRepo;
        $this->recordRepo = $recordRepo;
    }

    /**
     * Get identity details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublic(): JsonResponse
    {
        $address = auth_address();
        $email = $this->identityRepo->getPrimaryEmail($address);
        $bsn = !empty($this->recordRepo->bsnByAddress($address));

        return response()->json(compact('address', 'email', 'bsn'));
    }

    /**
     * Create new identity (registration)
     *
     * @param IdentityStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(
        IdentityStoreRequest $request
    ): JsonResponse {
        $this->throttleWithKey('to_many_attempts', $request, 'auth');

        // client type, key and primary email
        $clientKey = implementation_key();
        $clientType = client_type();
        $primaryEmail = $request->input('email', $request->input('records.primary_email'));

        // build records list and remove bsn and primary_email
        $records = collect($request->input('records', []));
        $records = $records->filter(function($value, $key) {
            return !empty($value) && !in_array($key, ['bsn', 'primary_email']);
        })->toArray();

        // make identity and exchange_token
        $identityAddress = $this->identityRepo->makeByEmail($primaryEmail, $records);
        $identityProxy = $this->identityRepo->makeIdentityPoxy($identityAddress);
        $exchangeToken = $identityProxy['exchange_token'];
        $isMobile = in_array($clientType, config('forus.clients.mobile'), true);

        $queryParams = sprintf("?%s", http_build_query(array_merge(
            $request->only('target'), [
                'client_type' => $clientType,
                'implementation_key' => $clientKey,
                'is_mobile' => $isMobile ? 1 : 0,
            ]
        )));

        // build confirmation link
        $confirmationLink = url(sprintf(
            '/api/v1/identity/proxy/confirmation/redirect/%s%s',
            $exchangeToken,
            $queryParams
        ));

        // send confirmation email
        $this->mailService->sendEmailConfirmationLink(
            $primaryEmail,
            $clientType,
            Implementation::emailFrom(),
            $confirmationLink
        );

        return response()->json(null, 201);
    }

    /**
     * Validate email for registration, format and if it's already in the system
     *
     * @param IdentityStoreValidateEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function storeValidateEmail(
        IdentityStoreValidateEmailRequest $request
    ): JsonResponse {
        $this->throttleWithKey('to_many_attempts', $request, 'auth');

        $email = (string) $request->input('email', '');
        $used = !$this->identityRepo->isEmailAvailable($email);

        return response()->json([
            'email' => [
                'used' => $used,
                'unique' => !$used,
                'valid' => validate_data(compact('email'), [
                    'email' => 'required|email'
                ])->passes(),
            ]
        ], 200);
    }

    /**
     * Redirect from email confirmation link to one of the front-ends or
     * show a button with deep link to mobile app
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $exchangeToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     * @throws \Exception
     */
    public function emailConfirmationRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $exchangeToken
    ) {
        $token = $exchangeToken;
        $isMobile = $request->input('is_mobile', false);

        $target = $request->input('target', false);
        $clientType = $request->input('client_type', '');
        $implementationKey = $request->input('implementation_key');

        if ((!$isMobile || $clientType) &&
            !in_array($clientType, array_flatten(config('forus.clients')), true)) {
            abort(404, "Invalid client type.");
        }

        if ((!$isMobile || $clientType) &&
            !Implementation::isValidKey($implementationKey)) {
            abort(404, "Invalid implementation key.");
        }

        $isWebFrontend = in_array($clientType, array_merge(
            config('forus.clients.webshop'),
            config('forus.clients.dashboards')
        ), true);

        if ($isWebFrontend) {
            $webShopUrl = Implementation::byKey($implementationKey);
            $webShopUrl = $webShopUrl['url_' . $clientType];

            return redirect($redirectUrl = sprintf(
                $webShopUrl . "confirmation/email/%s?%s",
                $exchangeToken,
                http_build_query(compact('target'))
            ));
        }

        if ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
            $redirectUrl = sprintf(
                $sourceUrl . "identity-confirmation?%s",
                http_build_query(compact('token'))
            );

            return view()->make('pages.auth.deep_link', array_merge([
                'type' => 'email_confirmation'
            ], compact('redirectUrl', 'exchangeToken')));
        }

        abort(404);
    }

    /**
     * Exchange email confirmation token for access_token
     *
     * @param $exchangeToken
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailConfirmationExchange(
        string $exchangeToken
    ): JsonResponse {
        return response()->json([
            'access_token' => $this->identityRepo->exchangeEmailConfirmationToken($exchangeToken)
        ], 200);
    }

    /**
     * Make new email authorization request
     *
     * @param IdentityAuthorizationEmailTokenRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function proxyAuthorizationEmailToken(
        IdentityAuthorizationEmailTokenRequest $request
    ): JsonResponse {
        $this->throttleWithKey('to_many_attempts', $request, 'auth');

        $email = $request->input('email', $request->input('primary_email'));
        $source = sprintf('%s_%s', implementation_key(), client_type());
        $isMobile = in_array(client_type(), config('forus.clients.mobile'), true);

        $identityId = $this->recordRepo->identityAddressByEmail($email);
        $proxy = $this->identityRepo->makeAuthorizationEmailProxy($identityId);

        $redirect_link = url(sprintf(
            '/api/v1/identity/proxy/email/redirect/%s?%s',
            $proxy['exchange_token'],
            http_build_query(array_merge([
                'target' => $request->input('target', ''),
                'is_mobile' => $isMobile ? 1 : 0
            ], $isMobile ? [] : [
                'client_type' => client_type(),
                'implementation_key' => implementation_key(),
            ]))
        ));

        $this->mailService->loginViaEmail(
            $email,
            Implementation::emailFrom(),
            $redirect_link,
            $source
        );

        return response()->json(null, 201);
    }

    /**
     * Redirect from email sign in link to one of the front-ends or
     * show a button with deep link to mobile app
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $emailToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function emailTokenRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $emailToken
    ) {
        $exchangeToken = $emailToken;
        $clientType = $request->input('client_type');
        $implementationKey = $request->input('implementation_key');
        $isMobile = $request->input('is_mobile', false);

        if ((!$isMobile || $clientType) &&
            !in_array($clientType, array_flatten(config('forus.clients')), true)) {
            abort(404, "Invalid client type.");
        }

        if ((!$isMobile || $clientType) &&
            !Implementation::isValidKey($implementationKey)) {
            abort(404, "Invalid implementation key.");
        }

        if ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
        } else if ($implementationKey === 'general') {
            $sourceUrl = Implementation::general_urls()['url_' . $clientType];
        } else {
            $sourceUrl = Implementation::query()->where([
                'key' => $implementationKey
            ])->first()['url_' . $clientType];
        }

        $redirectUrl = sprintf(
            $sourceUrl . "identity-restore?%s",
            http_build_query(array_filter([
                'token' => $emailToken,
                'target' => $request->input('target', null)
            ], static function($var) {
                return !empty($var);
            }))
        );

        if ($isMobile) {
            return view('pages.auth.deep_link', array_merge([
                'type' => 'email_sign_in'
            ], compact('redirectUrl', 'exchangeToken')));
        }

        return redirect($redirectUrl);
    }

    /**
     * Exchange email sign in token for access_token
     *
     * @param string $emailToken
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailTokenExchange(
        string $emailToken
    ): JsonResponse {
        return response()->json([
            'access_token' => $this->identityRepo->activateAuthorizationEmailProxy($emailToken)
        ], 200);
    }

    /**
     * Make new pin code authorization request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyAuthorizationCode(): JsonResponse {
        $proxy = $this->identityRepo->makeAuthorizationCodeProxy();

        return response()->json([
            'access_token' => $proxy['access_token'],
            'auth_code' => (int) $proxy['exchange_token'],
        ], 201);
    }

    /**
     * Authorize pin code authorization request
     *
     * @param IdentityAuthorizeCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyAuthorizeCode(
        IdentityAuthorizeCodeRequest $request
    ): JsonResponse {
        $this->identityRepo->activateAuthorizationCodeProxy(
            $request->auth_address(),
            $request->post('auth_code', '')
        );

        return response()->json(null, 200);
    }

    /**
     * Make new auth token (qr-code) authorization request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyAuthorizationToken(): JsonResponse {
        $proxy = $this->identityRepo->makeAuthorizationTokenProxy();

        return response()->json([
            'access_token' => $proxy['access_token'],
            'auth_token' => $proxy['exchange_token'],
        ], 201);
    }

    /**
     * Authorize auth code (qr-code) authorization request
     *
     * @param IdentityAuthorizeTokenRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyAuthorizeToken(
        IdentityAuthorizeTokenRequest $request
    ): JsonResponse {
        $this->identityRepo->activateAuthorizationTokenProxy(
            $request->auth_address(),
            $request->post('auth_token', '')
        );

        return response()->json(null, 200);
    }

    /**
     * Create and activate a short living token for current user
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function proxyAuthorizationShortToken(): JsonResponse {
        $proxy = $this->identityRepo->makeAuthorizationShortTokenProxy();
        $exchange_token = $proxy['exchange_token'];

        $this->identityRepo->activateAuthorizationShortTokenProxy(auth_address(), $exchange_token);

        return response()->json(compact('exchange_token'), 201);
    }

    /**
     * Exchange `short_token` for `access_token`
     *
     * @param string $shortToken
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyExchangeAuthorizationShortToken(
        string $shortToken
    ): JsonResponse {
        return response()->json([
            'access_token' => $this->identityRepo->exchangeAuthorizationShortTokenProxy($shortToken)
        ], 200);
    }

    /**
     * Check access_token state
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request): JsonResponse
    {
        $accessToken = $request->header('Access-Token', null);
        $proxyIdentityId = $this->identityRepo->proxyIdByAccessToken($accessToken);
        $identityAddress = $this->identityRepo->identityAddressByProxyId($proxyIdentityId);
        $proxyIdentityState = $this->identityRepo->proxyStateById($proxyIdentityId);
        $message = 'active';

        if ($proxyIdentityState === 'pending') {
            $message = 'pending';
        } elseif (!$accessToken || !$proxyIdentityId || !$identityAddress) {
            $message = 'invalid';
        }

        return response()->json(compact('message'), 200);
    }

    /**
     * Destroy an access token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function proxyDestroy(
        Request $request
    ): JsonResponse {
        $proxyDestroy = $request->get('proxyIdentity');

        $this->identityRepo->destroyProxyIdentity($proxyDestroy);

        return response()->json(null, 200);
    }
}
