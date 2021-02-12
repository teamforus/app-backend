<?php

namespace App\Models;

use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Http\Request;

/**
 * App\Models\Implementation
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $key
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property string $url_webshop
 * @property string $url_sponsor
 * @property string $url_provider
 * @property string $url_validator
 * @property string $url_app
 * @property float|null $lon
 * @property float|null $lat
 * @property bool $informal_communication
 * @property string|null $email_from_address
 * @property string|null $email_from_name
 * @property bool $digid_enabled
 * @property bool $digid_required
 * @property string $digid_env
 * @property string|null $digid_app_id
 * @property string|null $digid_shared_secret
 * @property string|null $digid_a_select_server
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundConfig[] $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\ImplementationPage|null $page_accessibility
 * @property-read \App\Models\ImplementationPage|null $page_explanation
 * @property-read \App\Models\ImplementationPage|null $page_privacy
 * @property-read \App\Models\ImplementationPage|null $page_provider
 * @property-read \App\Models\ImplementationPage|null $page_terms_and_conditions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ImplementationPage[] $pages
 * @property-read int|null $pages_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidASelectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidSharedSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereInformalCommunication($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 */
class Implementation extends Model
{
    public const KEY_GENERAL = 'general';

    public const FRONTEND_WEBSHOP = 'webshop';
    public const FRONTEND_SPONSOR_DASHBOARD = 'sponsor';
    public const FRONTEND_PROVIDER_DASHBOARD = 'provider';
    public const FRONTEND_VALIDATOR_DASHBOARD = 'validator';

    public const FRONTEND_KEYS = [
        self::FRONTEND_WEBSHOP,
        self::FRONTEND_SPONSOR_DASHBOARD,
        self::FRONTEND_PROVIDER_DASHBOARD,
        self::FRONTEND_VALIDATOR_DASHBOARD,
    ];

    protected $perPage = 20;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat', 'email_from_address', 'email_from_name',
        'title', 'description', 'informal_communication',
        'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'lon' => 'float',
        'lat' => 'float',
        'digid_enabled' => 'boolean',
        'digid_required' => 'boolean',
        'informal_communication' => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ImplementationPage::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_explanation(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_EXPLANATION,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_provider(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_PROVIDER,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_privacy(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_PRIVACY,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_accessibility(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_ACCESSIBILITY,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_terms_and_conditions(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_TERMS_AND_CONDITIONS,
        ]);
    }

    /**
     * @return HasManyThrough
     */
    public function funds(): HasManyThrough
    {
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return array|string|null
     */
    public static function activeKey()
    {
        return request()->header('Client-Key', self::KEY_GENERAL);
    }

    /**
     * @return Implementation
     */
    public static function active(): Implementation
    {
        return self::byKey(self::activeKey());
    }

    /**
     * @param $key
     * @return Implementation
     */
    public static function byKey($key): Implementation
    {
        /** @var self $model */
        $model = self::where(compact('key'))->first();

        return $model;
    }

    /**
     * @param $key
     * @return bool
     */
    public static function isValidKey($key): bool
    {
        return self::implementationKeysAvailable()->search($key) !== false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_configs(): HasMany
    {
        return $this->hasMany(FundConfig::class);
    }

    /**
     * @return Builder
     */
    public static function activeFundsQuery(): Builder
    {
        return self::queryFundsByState('active');
    }

    /**
     * @param $states
     * @return Builder
     */
    public static function queryFundsByState($states): Builder
    {
        /** @var Builder $query */
        $query = Fund::query()->has('fund_config')->whereIn('state', (array)$states);

        if (self::activeKey() !== self::KEY_GENERAL) {
            $query->whereHas('fund_config.implementation', static function (Builder $builder) {
                $builder->where('key', self::activeKey());
            });
        }

        return $query;
    }

    /**
     * @return Collection
     */
    public static function activeFunds(): Collection
    {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return Collection
     */
    public static function implementationKeysAvailable(): Collection
    {
        return self::query()->pluck('key');
    }

    /**
     * @return Collection
     */
    public static function keysAvailable(): Collection
    {
        return self::implementationKeysAvailable()->map(static function ($key) {
            return [
                $key . '_webshop',
                $key . '_sponsor',
                $key . '_provider',
                $key . '_validator',
                $key . '_website',
            ];
        })->flatten()->merge(config('forus.clients.mobile'))->values();
    }

    /**
     * @return bool
     */
    public function digidEnabled(): bool
    {
        $digidConfigured =
            !empty($this->digid_app_id) &&
            !empty($this->digid_shared_secret) &&
            !empty($this->digid_a_select_server);

        return $this->digid_enabled && $digidConfigured;
    }

    /**
     * @return DigIdRepo
     * @throws \App\Services\DigIdService\DigIdException
     */
    public function getDigid(): DigIdRepo
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
     * @return string|null
     */
    public function urlFrontend(string $frontend, string $uri = ''): ?string
    {
        switch ($frontend) {
            case 'webshop':
                return $this->urlWebshop($uri);
            case 'sponsor':
                return $this->urlSponsorDashboard($uri);
            case 'provider':
                return $this->urlProviderDashboard($uri);
            case 'validator':
                return $this->urlValidatorDashboard($uri);
        }

        return null;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlWebshop(string $uri = "/"): string
    {
        return http_resolve_url($this->url_webshop, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlSponsorDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_sponsor, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlProviderDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_provider, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlValidatorDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_validator, $uri);
    }

    /**
     * @return bool
     */
    public function autoValidationEnabled(): bool
    {
        $oneActiveFund = $this->funds()->where(['state' => Fund::STATE_ACTIVE])->count() === 1;
        $oneActiveFundWithAutoValidation = $this->funds()->where([
                'state' => Fund::STATE_ACTIVE,
                'auto_requests_validation' => true
            ])->whereNotNull('default_validator_employee_id')->count() === 1;

        return $oneActiveFund && $oneActiveFundWithAutoValidation;
    }

    /**
     * @param $value
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|void
     */
    public static function platformConfig($value)
    {
        if (!self::isValidKey(self::activeKey())) {
            abort(403, 'unknown_implementation_key');
        }

        $ver = request()->input('ver');

        if (preg_match('/[^a-z_\-0-9]/i', $value) || preg_match('/[^a-z_\-0-9]/i', $ver)) {
            abort(403);
        }

        $config = config('forus.features.' . $value . ($ver ? '.' . $ver : ''));

        if (is_array($config)) {
            $implementation = self::active();

            $config = array_merge($config, [
                'media' => self::getPlatformMediaConfig(),
                'has_budget_funds' => self::hasFundsOfType(Fund::TYPE_BUDGET),
                'has_subsidy_funds' => self::hasFundsOfType(Fund::TYPE_SUBSIDIES),
                'digid' => $implementation->digidEnabled(),
                'digid_mandatory' => $implementation->digid_required ?? true,
                'communication_type' => ($implementation->informal_communication ?? false ? 'informal' : 'formal'),
                'settings' => $implementation->only('title', 'description', 'description_html'),
                'fronts' => $implementation->only([
                    'url_webshop', 'url_sponsor', 'url_provider', 'url_validator', 'url_app'
                ]),
                'map' => $implementation->only('lon', 'lat'),
                'implementation_name' => $implementation->name,
                'products_hard_limit' => config('forus.features.dashboard.organizations.products.hard_limit'),
                'products_soft_limit' => config('forus.features.dashboard.organizations.products.soft_limit'),
                'pages' => $implementation->getPages(),
            ]);
        }

        return $config ?: [];
    }


    /**
     * @param string $type
     * @return bool
     */
    public static function hasFundsOfType(string $type): bool
    {
        return self::activeFundsQuery()->where('type', $type)->exists();
    }

    /**
     * @return Collection
     */
    private static function getPlatformMediaConfig(): Collection
    {
        return collect(MediaService::getMediaConfigs())->map(static function (
            MediaImageConfig $mediaConfig
        ) {
            return [
                'aspect_ratio' => $mediaConfig->getPreviewAspectRatio(),
                'size' => collect($mediaConfig->getPresets())->map(static function (
                    MediaPreset $mediaPreset
                ) {
                    return $mediaPreset instanceof MediaImagePreset ? [
                        $mediaPreset->width,
                        $mediaPreset->height,
                        $mediaPreset->preserve_aspect_ratio,
                    ] : null;
                })
            ];
        });
    }

    /**
     * @param Request $request
     * @return Organization|Builder
     */
    public static function searchProviders(Request $request)
    {
        /** @var Builder $query */
        $query = Organization::query();

        $query->whereHas('supplied_funds_approved', static function (Builder $builder) {
            $builder->whereIn('funds.id', self::activeFundsQuery()->pluck('funds.id'));
        });

        if ($request->has('business_type_id') && (
            $business_type = $request->input('business_type_id'))
        ) {
            $query->whereHas('business_type', static function (
                Builder $builder
            ) use ($business_type) {
                $builder->where('id', $business_type);
            });
        }

        if ($request->has('fund_id') && ($fund_id = $request->input('fund_id'))) {
            $query->whereHas('supplied_funds_approved', static function (
                Builder $builder
            ) use ($fund_id) {
                $builder->where('funds.id', $fund_id);
            });
        }

        if ($request->has('q') && ($q = $request->input('q'))) {
            $query->where(static function (Builder $builder) use ($q) {
                $like = '%' . $q . '%';

                $builder->where('name', 'LIKE', $like);

                $builder->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('email_public', true);
                    $builder->where('email', 'LIKE', $like);
                })->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('phone_public', true);
                    $builder->where('phone', 'LIKE', $like);
                })->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('website_public', true);
                    $builder->where('website', 'LIKE', $like);
                });

                $builder->orWhereHas('business_type.translations', static function (
                    Builder $builder
                ) use ($like) {
                    $builder->where('business_type_translations.name', 'LIKE', $like);
                });

                $builder->orWhereHas('offices', static function (
                    Builder $builder
                ) use ($like) {
                    $builder->where(static function (Builder $query) use ($like) {
                        $query->where(
                            'address', 'LIKE', $like
                        );
                    });
                });
            });
        }

        return $query;
    }

    /**
     * @param string|null $key
     * @return EmailFrom
     */
    public static function emailFrom(?string $key = null): EmailFrom
    {
        if ($implementation = ($key ? self::byKey($key) : self::active())) {
            return $implementation->getEmailFrom();
        }

        return EmailFrom::createDefault();
    }

    /**
     * @return EmailFrom
     */
    public function getEmailFrom(): EmailFrom
    {
        return new EmailFrom(
            $this->email_from_address ?: config('mail.from.address'),
            $this->email_from_name ?: config('mail.from.name'),
            $this->informal_communication ?? false
        );
    }

    /**
     * @return bool
     */
    public function isGeneral(): bool
    {
        return $this->key === self::KEY_GENERAL;
    }

    /**
     * @return Implementation
     */
    public static function general(): Implementation
    {
        return self::byKey(self::KEY_GENERAL);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return resolve('markdown')->convertToHtml($this->description ?? '');
    }

    /**
     * @param array $pages
     * @return $this
     */
    public function updatePages(array $pages): self
    {
        foreach ($pages as $pageType => $pageData) {
            $pageModel = $this->pages()->firstOrCreate([
                'page_type' => $pageType,
            ]);

            $pageModel->update(array_merge(array_only($pageData, [
                'content', 'external', 'external_url',
            ]), in_array($pageType, ImplementationPage::TYPES_INTERNAL) ? [
                'external' => 0,
                'external_url' => null,
            ] : []));
        }

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    private function getPages()
    {
        $pages = self::general()->pages;

        if (!$this->isGeneral()) {
            foreach (ImplementationPage::TYPES as $page_type) {
                $localPages = $this->pages->filter(function(ImplementationPage $page) use ($page_type) {
                    return $page->page_type === $page_type && (
                        $page->external ? $page->external_url : $page->content);
                });

                if ($localPages->count() > 0) {
                    $pageIndex = $pages->find($localPages->first());
                    $pages[$pageIndex] = $localPages->first();
                }
            }
        }

        return $pages->map(static function(ImplementationPage $page) {
            return array_merge($page->only('page_type', 'external'), [
                'content_html' => $page->external ? '' : $page->content_html,
                'external_url' => $page->external ? $page->external_url : '',
            ]);
        })->keyBy('page_type');
    }
}
