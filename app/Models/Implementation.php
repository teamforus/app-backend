<?php

namespace App\Models;

use App\Services\DigIdService\Repositories\DigIdRepo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\Implementation
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $url_webshop
 * @property string $url_sponsor
 * @property string $url_provider
 * @property string $url_validator
 * @property string $url_app
 * @property float|null $lon
 * @property float|null $lat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 * @property string|null $digid_app_id
 * @property string|null $digid_shared_secret
 * @property string|null $digid_a_select_server
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidASelectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidSharedSecret($value)
 * @property bool $digid_enabled
 * @property string $digid_env
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnv($value)
 */
class Implementation extends Model
{
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat'
    ];

    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server'
    ];

    protected $casts = [
        'digid_enabled' => 'boolean'
    ];

    const FRONTEND_WEBSHOP = 'webshop';
    const FRONTEND_SPONSOR_DASHBOARD = 'sponsor';
    const FRONTEND_PROVIDER_DASHBOARD = 'provider';
    const FRONTEND_VALIDATOR_DASHBOARD = 'validator';

    const FRONTEND_KEYS = [
        self::FRONTEND_WEBSHOP,
        self::FRONTEND_SPONSOR_DASHBOARD,
        self::FRONTEND_PROVIDER_DASHBOARD,
        self::FRONTEND_VALIDATOR_DASHBOARD,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds() {
        return $this->hasManyThrough(
            Fund::class,
            FundConfig::class,
            'implementation_id',
            'id',
            'id',
            'fund_id'
        );
    }

    /**
     * @param string $default
     * @return array|string
     */
    public static function activeKey($default = 'general') {
        return request()->header('Client-Key', $default);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function active() {
        return self::byKey(self::activeKey());
    }

    /**
     * @param $key
     * @return \Illuminate\Support\Collection
     */
    public static function byKey($key) {
        if ($key == 'general') {
            return collect(self::general_urls());
        }

        return collect(self::query()->where(compact('key'))->first());
    }

    /**
     * @param $key
     * @return Implementation|null
     */
    public static function findModelByKey($key) {
        /** @var Implementation|null $implementation */
        $implementation = self::query()->where(compact('key'))->first();
        return $implementation;
    }

    /**
     * @return Implementation|null
     */
    public static function activeModel() {
        return self::findModelByKey(self::activeKey());
    }

    public static function general_urls() {
        return [
            'url_webshop'   => config('forus.front_ends.webshop'),
            'url_sponsor'   => config('forus.front_ends.panel-sponsor'),
            'url_provider'  => config('forus.front_ends.panel-provider'),
            'url_validator' => config('forus.front_ends.panel-validator'),
            'url_website'   => config('forus.front_ends.website-general'),
            'url_app'       => config('forus.front_ends.landing-app'),
            'lon'           => config('forus.front_ends.map.lon'),
            'lat'           => config('forus.front_ends.map.lat')
        ];
    }

    /**
     * @param $key
     * @return bool
     */
    public static function isValidKey($key) {
        return self::implementationKeysAvailable()->search($key) !== false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function activeFundsQuery() {
        return self::queryFundsByState('active');
    }

    /**
     * @param $states
     * @return Fund|\Illuminate\Database\Eloquent\Builder|Builder
     */
    public static function queryFundsByState($states) {
        $states = (array) $states;

        if (self::activeKey() == 'general') {
            return Fund::query()->has('fund_config')->whereIn('state', $states);
        }

        return Fund::query()->whereIn('id', function(Builder $query) {
            $query->select('fund_id')->from('fund_configs')->where([
                'implementation_id' => Implementation::query()->where([
                    'key' => self::activeKey()
                ])->first()->id
            ]);
        })->whereIn('state', $states);
    }

    /**
     * @return Collection
     */
    public static function activeFunds() {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return Collection
     */
    public static function activeProductCategories() {
        if (self::activeKey() == 'general') {
            return ProductCategory::all();
        }

        return ProductCategory::query()->whereIn(
            'id', FundProductCategory::query()->whereIn(
            'fund_id', self::activeFunds()->pluck('id')
        )->pluck('product_category_id')->unique())->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function implementationKeysAvailable() {
        return self::query()->pluck('key')->merge([
            'general'
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function keysAvailable () {
        return self::implementationKeysAvailable()->map(function ($key) {
            return [
                $key . '_webshop',
                $key . '_sponsor',
                $key . '_provider',
                $key . '_validator',
                $key . '_website',
            ];
        })->flatten()->merge([
            'app-me_app'
        ])->values();
    }

    /**
     * @return bool
     */
    public function digidEnabled() {
        return $this->digid_enabled && !empty($this->digid_app_id) && !empty(
            $this->digid_shared_secret
            ) && !empty($this->digid_a_select_server);
    }

    /**
     * @return DigIdRepo
     * @throws \App\Services\DigIdService\DigIdException
     */
    public function getDigid()
    {
        return new DigIdRepo(
            $this->digid_env,
            $this->digid_app_id,
            $this->digid_shared_secret,
            $this->digid_a_select_server
        );
    }

    /**
     * @param string $frontend
     * @param string $uri
     * @return mixed|string|null
     */
    public function urlFrontend(string $frontend, string $uri = '') {
        switch ($frontend) {
            case 'webshop': return $this->urlWebshop($uri); break;
            case 'sponsor': return $this->urlSponsorDashboard($uri); break;
            case 'provider': return $this->urlProviderDashboard($uri); break;
            case 'validator': return $this->urlValidatorDashboard($uri); break;
        }
        return null;
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlWebshop(string $uri = "/")
    {
        return http_resolve_url($this->url_webshop ?? env('WEB_SHOP_GENERAL_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlSponsorDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_sponsor ?? env('PANEL_SPONSOR_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlProviderDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_provider ?? env('PANEL_PROVIDER_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlValidatorDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_validator ?? env('PANEL_VALIDATOR_URL'), $uri);
    }
}
