<?php

namespace App\Models;

use App\Events\Products\ProductSoldOut;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $organization_id
 * @property int $product_category_id
 * @property string $name
 * @property string $description
 * @property float $price
 * @property float|null $old_price
 * @property int $total_amount
 * @property bool $unlimited_stock
 * @property bool $no_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $expire_at
 * @property bool $sold_out
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read bool $expired
 * @property-read bool $is_offer
 * @property-read int $stock_amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Services\MediaService\Models\Media|null $photo
 * @property-read \App\Models\ProductCategory $product_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProductExclusion[] $product_exclusions
 * @property-read int|null $product_exclusions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers_reserved
 * @property-read int|null $vouchers_reserved_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereNoPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOldPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSoldOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUnlimitedStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withoutTrashed()
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasMedia, SoftDeletes, HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_SOLD_OUT = 'sold_out';
    public const EVENT_EXPIRED = 'expired';
    public const EVENT_RESERVED = 'reserved';

    public const EVENT_APPROVED = 'approved';
    public const EVENT_REVOKED = 'revoked';
    public const EVENT_ACTIONS_REMOVED = 'actions_removed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'organization_id', 'product_category_id',
        'price', 'old_price', 'total_amount', 'expire_at', 'sold_out',
        'unlimited_stock', 'no_price',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public $dates = [
        'expire_at', 'deleted_at'
    ];

    protected $casts = [
        'no_price' => 'boolean',
        'unlimited_stock' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers(): HasMany {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers_reserved(): HasMany {
        return $this->hasMany(Voucher::class)->whereDoesntHave('transactions');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions(): HasMany {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category(): BelongsTo {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds(): HasManyThrough {
        return $this->hasManyThrough(
            Fund::class,
            FundProduct::class
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fund_providers(): BelongsToMany {
        return $this->belongsToMany(
            FundProvider::class,
            'fund_provider_products'
        )->whereNull('fund_provider_products.deleted_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_products(): HasMany {
        return $this->hasMany(FundProviderProduct::class);
    }

    /**
     * @return HasMany
     */
    public function product_exclusions(): HasMany {
        return $this->hasMany(FundProviderProductExclusion::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo(): MorphOne {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'product_photo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_chats(): HasMany {
        return $this->hasMany(FundProviderChat::class);
    }

    /**
     * The product is offer
     *
     * @return bool
     */
    public function getIsOfferAttribute(): bool {
        return (bool) $this->old_price;
    }

    /**
     * The product is sold out
     *
     * @param $value
     * @return bool
     */
    public function getSoldOutAttribute($value): bool {
        return (bool) $value;
    }

    /**
     * The product is expired
     *
     * @return bool
     */
    public function getExpiredAttribute(): bool {
        return $this->expire_at->isPast();
    }

    /**
     * Count vouchers generated for this product but not used
     *
     * @return int
     */
    public function countReserved(): int {
        return $this->vouchers()->doesntHave('transactions')->count();
    }

    /**
     * Count actually sold products
     *
     * @return int
     */
    public function countSold(): int {
        return $this->voucher_transactions()->count();
    }

    /**
     * @return int
     */
    public function getStockAmountAttribute(): int {
        return $this->total_amount - (
            $this->vouchers_reserved->count() +
            $this->voucher_transactions->count());
    }

    /**
     * Update sold out state for the product
     */
    public function updateSoldOutState(): void {
        if (!$this->unlimited_stock) {
            $totalProducts = $this->countReserved() + $this->countSold();
            $sold_out = $totalProducts >= $this->total_amount;
            $this->update(compact('sold_out'));

            if ($sold_out) {
                broadcast(new ProductSoldOut($this));
            }
        }
    }

    /**
     * @return Builder
     */
    public static function searchQuery(): Builder {
        $query = self::query();
        $activeFunds = Implementation::activeFundsQuery()->pluck('id')->toArray();

        // only in stock and not expired
        $query = ProductQuery::inStockAndActiveFilter($query);

        // only approved by at least one sponsor
        $query = ProductQuery::approvedForFundsFilter($query, $activeFunds);

        return $query;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request): Builder {
        $query = self::searchQuery()->orderBy('created_at', 'desc');

        // filter by fund_type
        if ($fund_type = $request->input('fund_type')) {
            /** @var Builder $funds */
            $funds = Implementation::activeFundsQuery()->where('type', '=', $fund_type);
            $query = ProductQuery::approvedForFundsAndActiveFilter(
                $query, $funds->pluck('id')->toArray()
            );
        }

        // filter by product_category_id
        if ($category_id = $request->input('product_category_id')) {
            $query = ProductQuery::productCategoriesFilter($query, $category_id);
        }

        // filter by fund_id
        if ($request->has('fund_id') && $fund_id = $request->input('fund_id')) {
            $query = ProductQuery::approvedForFundsFilter($query, $fund_id);
        }

        // filter by no_price
        if ($request->has('no_price')) {
            $query = $query->where('no_price', '=', (bool) $request->input('no_price'));
        }

        // filter by unlimited stock
        if ($request->has('unlimited_stock') &&
            $unlimited_stock = filter_bool($request->input('unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($query, $unlimited_stock);
        }

        // filter by string query
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            return ProductQuery::queryDeepFilter($query, $q);
        }

        return $query;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function searchAny(Request $request): Builder {
        $query = self::query()->orderBy('created_at', 'desc');

        // filter by unlimited stock
        if ($request->has('unlimited_stock') &&
            $unlimited_stock = filter_bool($request->input('unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($query, $unlimited_stock);
        }

        // filter by string query
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            return ProductQuery::queryFilter($query, $q);
        }

        return $query;
    }

    /**
     * Send product sold out email to provider
     * @return void
     */
    public function sendSoldOutEmail(): void
    {
        $mailService = resolve('forus.services.notification');
        $mailService->productSoldOut(
            $this->organization->email,
            Implementation::emailFrom(),
            $this->name,
            Implementation::active()['url_provider']
        );
    }

    /**
     * @return string
     */
    public function getDescriptionHtmlAttribute(): string {
        return resolve('markdown')->convertToHtml($this->description);
    }

    /**
     * @param Fund $fund
     * @return null|FundProviderProduct
     */
    public function getSubsidyDetailsForFund(Fund $fund): ?FundProviderProduct {
        /** @var FundProviderProduct $fundProviderProduct */
        $fundProviderProduct = $this->fund_provider_products()->whereHas(
            'fund_provider.fund',
            static function(Builder $builder) use ($fund) {
            $builder->where([
                'fund_id' => $fund->id,
                'type' => $fund::TYPE_SUBSIDIES,
            ]);
        })->first();

        return $fundProviderProduct;
    }

    /**
     * @param Request $request
     */
    public function updateExclusions(Request $request): void
    {
        foreach ($request->input('disable_funds', []) as $fund_id) {
            /** @var FundProvider $fundProvider */
            if ($fundProvider = $this->organization->fund_providers()->where([
                'fund_id' => $fund_id
            ])->first()) {
                $fundProvider->product_exclusions()->firstOrCreate([
                    'product_id' => $this->id
                ]);

                $fundProvider->fund_provider_products()->where([
                    'product_id' => $this->id
                ])->delete();
            }
        }

        foreach ($request->input('enable_funds', []) as $fund_id) {
            /** @var FundProvider $fundProvider */
            if ($fundProvider = $this->organization->fund_providers()->where([
                'fund_id' => $fund_id
            ])->first()) {
                $fundProvider->product_exclusions()->where([
                    'product_id' => $this->id
                ])->delete();
            }
        }
    }
}
