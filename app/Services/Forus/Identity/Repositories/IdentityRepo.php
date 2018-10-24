<?php
namespace App\Services\Forus\Identity\Repositories;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Identity\Models\IdentityProxy;

class IdentityRepo implements Interfaces\IIdentityRepo
{
    // 10 years
    const PROXY_EXPIRES_IN = 60 * 60 * 24 * 365 * 10;

    protected $model;
    protected $recordRepo;

    public function __construct(
        Identity $model
    ) {
        $this->model = $model;
        $this->recordRepo = app('forus.services.record');
    }

    /**
     * Make new identity
     * @param string $pinCode
     * @param array $records
     * @throws \Exception
     * @return mixed
     */
    public function make(
        string $pinCode,
        array $records = []
    ) {
        $identity = $this->model->create(collect(
            app('key_pair_generator')->make()
        )->merge([
            'pin_code' => app('hash')->make($pinCode)
        ])->toArray())->toArray();

        $this->recordRepo->updateRecords($identity['address'], $records);

        return $identity['address'];
    }

    /**
     * Create new proxy for given identity
     * @param $identityAddress string
     * @return array
     */
    public function makeIdentityPoxy(
        $identityAddress
    ) {
        $identity = Identity::getModel()->where(
            'address', $identityAddress
        )->first();

        $proxyIdentity = $identity->proxies()->create([
            'access_token'  => $this->makeAccessToken(),
            'state'         => 'active',
            'expires_in'    => self::PROXY_EXPIRES_IN
        ]);

        return $proxyIdentity['id'];
    }

    /**
     * Get access_token by proxy identity id
     * @param $proxyIdentityId
     * @return mixed
     * @throws \Exception
     */
    public function getProxyAccessToken(
        $proxyIdentityId
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return $proxyIdentity['access_token'];
    }

    /**
     * Get proxy identity by access token
     * @param string $access_token
     * @return mixed
     */
    public function proxyIdByAccessToken(
        string $access_token = null
    ) {
        $proxyIdentity = IdentityProxy::getModel()->where([
            'access_token' => $access_token
        ])->first();

        return $proxyIdentity ? $proxyIdentity->id : null;
    }

    /**
     * Get proxy identity by access token
     * @param mixed $proxyIdentityId
     * @return string
     */
    public function identityAddressByProxyId(
        $proxyIdentityId = null
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if ($proxyIdentity && $proxyIdentity->identity) {
            return $proxyIdentity->identity->address;
        }

        return null;
    }

    /**
     * Get proxy identity state by id
     * @param mixed $proxyIdentityId
     * @return mixed
     */
    public function proxyStateById(
        $proxyIdentityId = null
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        return $proxyIdentity ? $proxyIdentity->state : null;
    }

    /**
     * Destroy proxy identity by id
     * @param mixed $proxyIdentityId
     * @return mixed|void
     * @throws \Exception
     */
    public function destroyProxyIdentity(
        $proxyIdentityId
    ) {
        IdentityProxy::whereKey($proxyIdentityId)->delete();
    }

    /**
     * @param $proxyIdentityId
     * @return bool
     * @throws \Exception
     */
    public function hasPinCode($proxyIdentityId) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return !!$proxyIdentity->identity->pin_code;
    }

    /**
     * @param mixed $proxyIdentityId
     * @param string $pinCode
     * @return bool
     * @throws \Exception
     */
    public function cmpPinCode(
        $proxyIdentityId,
        $pinCode
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return app('hash')->check(
            $pinCode,
            $proxyIdentity->identity->pin_code
        );
    }

    /**
     * @param $proxyIdentityId
     * @param string $pinCode
     * @param string $oldPinCode
     * @return bool
     * @throws \Exception
     */
    public function updatePinCode(
        $proxyIdentityId,
        $pinCode,
        $oldPinCode = null
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();


        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        if ($this->hasPinCode($proxyIdentityId) && !$this->cmpPinCode($proxyIdentityId, $oldPinCode)) {
            throw  new \Exception(
                trans('identity.exceptions.invalid_pin_code')
            );
        }

        $proxyIdentity->identity->update([
            'pin_code'  => app('hash')->make($pinCode)
        ]);

        return true;
    }

    /**
     * Make code authorization proxy identity
     * @return array
     */
    public function makeAuthorizationCodeProxy() {
        // 10 minutes
        $expiresIn = 60 * 10;

        do {
            $auth_code = $this->makeAuthCode();
        } while(IdentityProxy::getModel()->where([
            'auth_code'         => $auth_code,
            'state'             => 'pending'
        ])->count() > 0);

        $proxyIdentity = IdentityProxy::create([
            'access_token'      => $this->makeAccessToken(),
            'auth_code'         => $auth_code,
            'expires_in'        => $expiresIn,
            'state'             => "pending"
        ]);

        return collect($proxyIdentity)->only([
            'auth_code', 'access_token'
        ])->toArray();
    }


    /**
     * Make token authorization proxy identity
     * @return array
     */
    public function makeAuthorizationTokenProxy() {
        // 1 hour
        $expiresIn = 60 * 60;

        do {
            $auth_token = $this->makeAuthToken();
        } while(IdentityProxy::getModel()->where([
            'auth_token'        => $auth_token,
            'state'             => 'pending'
        ])->count() > 0);

        $proxyIdentity = IdentityProxy::create([
            'access_token'      => $this->makeAccessToken(),
            'auth_token'        => $auth_token,
            'expires_in'        => $expiresIn,
            'state'             => "pending"
        ]);

        return collect($proxyIdentity)->only([
            'auth_token', 'access_token'
        ])->toArray();
    }


    /**
     * Make email_token authorization proxy identity
     * @param string $identityAddress
     * @return array
     */
    public function makeAuthorizationEmailProxy(
        string $identityAddress
    ) {
        // 1 hour
        $expiresIn = 60 * 60;

        do {
            $auth_email_token = $this->makeAuthEmailToken();
        } while(IdentityProxy::getModel()->where([
            'auth_email_token'  => $auth_email_token
        ])->count() > 0);

        $proxyIdentity = IdentityProxy::create([
            'identity_address'  => $identityAddress,
            'auth_email_token'  => $auth_email_token,
            'expires_in'        => $expiresIn,
            'state'             => "pending"
        ]);

        return collect($proxyIdentity)->only([
            'auth_email_token'
        ])->toArray();
    }

    /**
     * Authorize proxy identity by code
     * @param $identityAddress string
     * @param string $code
     * @return mixed
     */
    public function activateAuthorizationCodeProxy(
        string $identityAddress,
        string $code
    ) {
        /** @var IdentityProxy $proxy */
        $proxy = IdentityProxy::getModel()->where([
            'auth_code' => $code
        ])->first();

        if (!$proxy) {
            return 'not-found';
        }

        if ($proxy->state != 'pending') {
            return 'not-pending';
        }

        $expire_at = $proxy->created_at->addSeconds(
            $proxy->expires_in
        )->timestamp;

        if ($expire_at < time()) {
            return 'expired';
        }

        return !!$proxy->update([
            'identity_address'  => $identityAddress,
            'state'             => 'active'
        ]);
    }

    /**
     * Authorize proxy identity by token
     * @param $identityAddress string
     * @param string $token
     * @return mixed
     */
    public function activateAuthorizationTokenProxy(
        string $identityAddress,
        string $token
    ) {
        /** @var IdentityProxy $proxy */
        $proxy = IdentityProxy::getModel()->where([
            'auth_token' => $token
        ])->first();

        if (!$proxy) {
            return 'not-found';
        }

        if ($proxy->state != 'pending') {
            return 'not-pending';
        }

        $expire_at = $proxy->created_at->addSeconds(
            $proxy->expires_in
        )->timestamp;

        if ($expire_at < time()) {
            return 'expired';
        }

        return !!$proxy->update([
            'identity_address'  => $identityAddress,
            'state'             => 'active'
        ]);
    }

    /**
     * Authorize proxy identity by email token
     * @param string $email_token
     * @return string
     */
    public function activateAuthorizationEmailProxy(string $email_token) {
        /** @var IdentityProxy $proxy */
        $proxy = IdentityProxy::getModel()->where([
            'auth_email_token' => $email_token
        ])->first();

        if (!$proxy) {
            abort(404, trans('identity-proxy.code.not-found'));
        }

        if ($proxy->state != 'pending') {
            abort(403, trans('identity-proxy.code.not-pending'));
        }

        $expire_at = $proxy->created_at->addSeconds(
            $proxy->expires_in
        )->timestamp;

        if ($expire_at < time()) {
            abort(403, trans('identity-proxy.code.expired'));
        }

        $access_token = $this->makeAccessToken();

        $proxy->update([
            'state'         => 'active',
            'access_token'  => $access_token
        ]);

        return $access_token;
    }

    private function makeToken($size) {
        return app('token_generator')->generate($size);
    }

    private function makeAccessToken() {
        return $this->makeToken(200);
    }

    private function makeAuthCode() {
        return random_int(000000, 999999);
    }

    private function makeAuthToken() {
        return $this->makeToken(64);
    }

    private function makeAuthEmailToken() {
        return $this->makeToken(64);
    }
}