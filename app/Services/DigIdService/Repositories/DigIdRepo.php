<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\Interfaces\IDigIdRepo;
use GuzzleHttp\Client;

class DigIdRepo implements IDigIdRepo
{
    const DIGID_SUCCESS                     = '0000';
    const DIGID_UNAVAILABLE                 = '0001';
    const DIGID_TEMPORARY_UNAVAILABLE_1     = '0003';
    const DIGID_VERIFICATION_FAILED_1       = '0004';
    const DIGID_VERIFICATION_FAILED_2       = '0007';
    const DIGID_ILLEGAL_REQUEST             = '0030';
    const DIGID_ERROR_APP_ID                = '0032';
    const DIGID_ERROR_ASELECT               = '0033';
    const DIGID_CANCELLED                   = '0040';
    const DIGID_BUSY                        = '0050';
    const DIGID_INVALID_SESSION             = '0070';
    const DIGID_WEBSERVICE_NOT_ACTIVE       = '0080';
    const DIGID_WEBSERVICE_NOT_AUTHORISED   = '0099';
    const DIGID_TEMPORARY_UNAVAILABLE_2     = '010c';

    const URL_API_SANDBOX = "https://was-preprod1.digid.nl/was/server";
    // TODO: set real production api
    const URL_API_PRODUCTION = "https://was-preprod1.digid.nl/was/server";

    const ENV_SANDBOX = "sandbox";
    const ENV_PRODUCTION = "production";

    protected $app_id = null;
    protected $shared_secret = null;
    protected $a_select_server = null;
    protected $environment = self::ENV_SANDBOX;

    /**
     * DigIdRepo constructor.
     * @param string $env
     * @param string $app_id
     * @param string $shared_secret
     * @param string $a_select_server
     * @throws DigIdException
     */
    public function __construct(
        string $env = self::ENV_SANDBOX,
        string $app_id = "",
        string $shared_secret = "",
        string $a_select_server = ""
    ) {
        if (!in_array($env, [self::ENV_SANDBOX, self::ENV_PRODUCTION])) {
            throw new DigIdException("Invalid environment.");
        }

        $this->app_id = $app_id;
        $this->shared_secret = $shared_secret;
        $this->a_select_server = $a_select_server;
        $this->environment = $env;
    }

    public static function responseCodeDetails($errorCode)
    {
        if ($errorCode == self::DIGID_SUCCESS) {
            return 'DIGID_SUCCESS';
        } elseif ($errorCode == self::DIGID_UNAVAILABLE) {
            return 'DIGID_UNAVAILABLE';
        } elseif ($errorCode == self::DIGID_TEMPORARY_UNAVAILABLE_1) {
            return 'DIGID_TEMPORARY_UNAVAILABLE_1';
        } elseif ($errorCode == self::DIGID_VERIFICATION_FAILED_1) {
            return 'DIGID_VERIFICATION_FAILED_1';
        } elseif ($errorCode == self::DIGID_VERIFICATION_FAILED_2) {
            return 'DIGID_VERIFICATION_FAILED_2';
        } elseif ($errorCode == self::DIGID_ILLEGAL_REQUEST) {
            return 'DIGID_ILLEGAL_REQUEST';
        } elseif ($errorCode == self::DIGID_ERROR_APP_ID) {
            return 'DIGID_ERROR_APP_ID';
        } elseif ($errorCode == self::DIGID_ERROR_ASELECT) {
            return 'DIGID_ERROR_ASELECT';
        } elseif ($errorCode == self::DIGID_CANCELLED) {
            return 'DIGID_CANCELLED';
        } elseif ($errorCode == self::DIGID_BUSY) {
            return 'DIGID_BUSY';
        } elseif ($errorCode == self::DIGID_INVALID_SESSION) {
            return 'DIGID_INVALID_SESSION';
        } elseif ($errorCode == self::DIGID_WEBSERVICE_NOT_ACTIVE) {
            return 'DIGID_WEBSERVICE_NOT_ACTIVE';
        } elseif ($errorCode == self::DIGID_WEBSERVICE_NOT_AUTHORISED) {
            return 'DIGID_WEBSERVICE_NOT_AUTHORISED';
        } elseif ($errorCode == self::DIGID_TEMPORARY_UNAVAILABLE_2) {
            return 'DIGID_TEMPORARY_UNAVAILABLE_2';
        }

        return $errorCode;
    }

    /**
     * @return string
     * @throws DigIdException
     */
    private function getApiUrl() {
        if ($this->environment == self::ENV_SANDBOX) {
            return self::URL_API_SANDBOX;
        } elseif ($this->environment == self::ENV_PRODUCTION) {
            return self::URL_API_PRODUCTION;
        } else {
            throw new DigIdException("Invalid environment.");
        }
    }

    /**
     * @return array
     */
    private function getAuthParams() {
        return [
            "app_id"            => $this->app_id,
            "shared_secret"     => $this->shared_secret,
            "a-select-server"   => $this->a_select_server,
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    private function makeAuthorizedRequest(array $params) {
        return array_merge($this->getAuthParams(), $params);
    }

    /**
     * @param array $params
     * @return string
     * @throws DigIdException
     */
    private function makeRequestUrl(array $params) {
        return sprintf("%s?%s", $this->getApiUrl(), http_build_query($params));
    }

    private function parseResponseBody($response) {
        $result = [];
        parse_str($response, $result);
        return $result;
    }

    private function validateAuthRequestResponse($result) {
        // Validate result
        $check = TRUE;

        // Should be alphanumeric.
        $check = $check && isset($result['rid']) && ctype_alnum($result['rid']);

        // URL.
        $check = $check && isset($result['as_url']) && $result['as_url'];

        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);

        // Should be numeric and 4 characters.
        $check = $check && isset($result['result_code']) && ctype_digit($result['result_code']);

        return $check;
    }

    private function validateVerifyCredentialsResponse($result) {
        // Validate result.
        $check = true;

        // Should be numeric.
        $check = $check && isset($result['uid']) && ctype_digit($result['uid']);
        // Should be alphanumeric.
        $check = $check && isset($result['app_id']) && ctype_alnum($result['app_id']);
        // Should be alphanumeric.
        $check = $check && isset($result['organization']) && ctype_alnum($result['organization']);
        // Should be numeric and 2 characters.
        $check = $check && isset($result['betrouwbaarheidsniveau']) && ctype_digit($result['betrouwbaarheidsniveau']);
        // Should be alphanumeric.
        $check = $check && isset($result['rid']) && ctype_alnum($result['rid']);
        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);
        // Should be numeric and 4 characters.
        $check = $check && isset($result['result_code']) && ctype_digit($result['result_code']);

        return $check;
    }

    /**
     * @param string $app_url
     * @param array $extraParams
     * @return array|bool
     * @throws DigIdException
     */
    public function makeAuthRequest($app_url = "", array $extraParams = [])
    {
        $request = $this->makeRequestUrl($this->makeAuthorizedRequest(array_merge([
            "request"           => "authenticate",
            "app_url"           => $app_url,
        ], $extraParams)));

        $response = (new Client())->get($request);
        $result = $this->parseResponseBody($response->getBody());
        $result_code = $result['result_code'] ?? false;

        if ($result_code !== self::DIGID_SUCCESS) {
            throw (new DigIdException(
                "Digid API error code received."
            ))->setDigIdCode($result_code);
        }

        if (!$this->validateAuthRequestResponse($result)) {
            throw (new DigIdException(
                "Digid invalid auth request, response body."
            ))->setDigIdCode($result_code);
        }

        return $result;
    }

    /**
     * @param string $rid
     * @param string $aselect_server
     * @param string $aselect_credentials
     * @return mixed
     * @throws DigIdException
     */
    public function getBsnFromResponse(
        string $rid,
        string $aselect_server,
        string $aselect_credentials
    ) {
        $request = $this->makeRequestUrl($this->makeAuthorizedRequest([
            'request'               => 'verify_credentials',
            'rid'                   => $rid,
            'a-select-server'       => $aselect_server,
            'aselect_credentials'   => $aselect_credentials,
        ]));

        $response = (new Client())->get($request);
        $result = $this->parseResponseBody($response->getBody());

        if ($response->getStatusCode() !== 200) {
            throw new DigIdException("DigiD: invalid response.");
        }

        $result_code = $result['result_code'] ?? null;

        if ($result_code == self::DIGID_CANCELLED) {
            throw (new DigIdException(
                "Digid API Request canceled."
            ))->setDigIdCode($result_code);
        }

        if (!$this->validateVerifyCredentialsResponse($result)) {
            throw (new DigIdException(
                "Digid invalid verify credentials request, response body."
            ))->setDigIdCode($result_code);
        }

        if ($result_code == self::DIGID_SUCCESS) {
            return $result;
        }

        throw (new DigIdException(
            "Digid API error code received."
        ))->setDigIdCode($result['result_code']);
    }
}