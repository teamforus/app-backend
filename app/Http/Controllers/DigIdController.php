<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;

/**
 * Class DigIdController
 * @package App\Http\Controllers
 */
class DigIdController extends Controller
{
    /**
     * @param StartDigIdRequest $request
     * @return array|void
     */
    public function start(StartDigIdRequest $request)
    {
        if ($request->input('request') !== 'auth') {
            $this->middleware('api.auth');
        }

        $digidSession = DigIdSession::createSession(
            $request->auth_address(),
            $request->implementation_model(),
            $request->client_type(),
            self::makeFinalRedirectUrl($request),
            $request->input('request')
        );

        $digidSession->startAuthSession(url(sprintf(
            '/api/v1/platform/digid/%s/resolve',
            $digidSession->session_uid
        )));

        if ($digidSession->state !== DigIdSession::STATE_PENDING_AUTH) {
            return abort(503, 'Unable to handle the request at the moment.', [
                'Error-Code' => strtolower('digid_' . $digidSession->digid_error_code),
            ]);
        }

        return [
            'redirect_url' => url(sprintf(
                '/api/v1/platform/digid/%s/redirect',
                $digidSession->session_uid
            ))
        ];
    }

    /**
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(DigIdSession $session) {
        return redirect($session->digid_auth_redirect_url);
    }

    /**
     * @param IRecordRepo $recordRepo
     * @param IIdentityRepo $identityRepo
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     * @throws \Exception
     */
    public function resolve(
        IRecordRepo $recordRepo,
        IIdentityRepo $identityRepo,
        ResolveDigIdRequest $request,
        DigIdSession $session
    ) {
        // check if session secret is match records
        if ($request->get('session_secret') !== $session->session_secret) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "unknown_error",
            ]));
        }

        // request BSN number from digid and store in session
        $session->requestBsn(
            $request->get('rid', ''),
            $request->get('a-select-server', ''),
            $request->get('aselect_credentials', '')
        );

        // check if digid request went well and redirect to final url with
        // error core if not
        if (!$session->isAuthorized()) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => $session->getErrorKey()
            ]));
        }

        switch ($session->session_request) {
            case 'auth': return $this->_resolveAuth($recordRepo, $identityRepo, $session);
            case 'fund_request': return $this->_resolveFundRequest($recordRepo, $session);
        }

        abort(503, 'Unknown session type.');
    }

    /**
     * @param IRecordRepo $recordRepo
     * @param IIdentityRepo $identityRepo
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    private function _resolveAuth(
        IRecordRepo $recordRepo,
        IIdentityRepo $identityRepo,
        DigIdSession $session
    ) {
        $identity = $recordRepo->identityAddressByBsn($session->digid_uid);

        if (empty($identity)) {
            return redirect(sprintf(
                 '%s/?digid_error=uid_not_found',
                rtrim($session->session_final_url, '/')
            ));
        }

        $proxy = $identityRepo->makeAuthorizationShortTokenProxy();
        $identityRepo->activateAuthorizationShortTokenProxy($identity, $proxy['exchange_token']);

        return redirect(sprintf(
            '%s/auth-link?token=%s',
            rtrim($session->session_final_url, '/'),
            $proxy['exchange_token']
        ));
    }

    /**
     * @param IRecordRepo $recordRepo
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    private function _resolveFundRequest(
        IRecordRepo $recordRepo,
        DigIdSession $session
    ) {
        $bsn = $session->digid_uid;
        $identity = $session->identity_address;
        $identity_bsn = $recordRepo->bsnByAddress($identity);
        $bsn_identity = $recordRepo->identityAddressByBsn($bsn);

        if ($identity_bsn && $bsn !== $identity_bsn) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_dont_match",
            ]));
        }

        if ($bsn_identity && $bsn_identity !== $identity) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_used",
            ]));
        }

        $isFirstSignUp = !$identity_bsn && !$bsn_identity;

        if ($isFirstSignUp) {
            $recordRepo->setBsnRecord($identity, $bsn);
        }

        Prevalidation::assignAvailableToIdentityByBsn($identity);
        Voucher::assignAvailableToIdentityByBsn($identity);

        return redirect(url_extend_get_params($session->session_final_url, [
            'digid_success' => $isFirstSignUp ? 'signed_up' : 'signed_in'
        ]));
    }

    /**
     * @param StartDigIdRequest $request
     * @return mixed|string|void|null
     */
    private static function makeFinalRedirectUrl(StartDigIdRequest $request) {
        $implementationModel = $request->implementation_model();
        $fund = Fund::find($request->input('fund_id'));

        if ($request->input('request') === 'fund_request') {
            return $fund->urlWebshop(sprintf('/funds/%s/activate', $fund->id));
        }

        if ($request->input('request') === 'auth') {
            return $implementationModel ? $implementationModel->urlFrontend(
                $request->client_type()
            ) : abort(404);
        }

        return abort(404);
    }
}
