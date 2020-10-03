<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class VoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources
 */
class VoucherResource extends Resource
{
    /**
     * @var array
     */
    public static $load = [
        'parent',
        'tokens',
        'transactions.voucher.fund.logo.presets',
        'transactions.provider.logo.presets',
        'transactions.product.photo.presets',
        'product_vouchers.product.photo.presets',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'product.organization.offices.schedules',
        'product.organization.offices.photo.presets',
        'product.organization.offices.organization.logo.presets',
        'fund.fund_config.implementation',
        'fund.provider_organizations_approved.offices.schedules',
        'fund.provider_organizations_approved.offices.photo.presets',
        'fund.provider_organizations_approved.offices.organization.logo.presets',
        'fund.logo.presets',
        'fund.organization.logo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $voucher = $this->resource;
        $physical_cards = $voucher->physical_cards()->first();

        return array_merge($voucher->only([
            'identity_address', 'fund_id', 'returnable'
        ]), $this->getBaseFields($voucher), [
            'created_at' => $voucher->created_at_string,
            'expire_at' => [
                'date' => $voucher->expire_at->format("Y-m-d H:i:s.00000"),
                'timeZone' => $voucher->expire_at->timezone->getName(),
            ],
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'last_active_day' => $voucher->last_active_day->format('Y-m-d'),
            'last_active_day_locale' => format_date_locale($voucher->last_active_day),
            'expired' => $voucher->expired,
            'created_at_locale' => $voucher->created_at_string_locale,
            'address' => $voucher->token_with_confirmation->address,
            'address_printable' => $voucher->token_without_confirmation->address,
            'timestamp' => $voucher->created_at->timestamp,
            'type' => $voucher->type,
            'fund' => $this->getFundResource($voucher->fund),
            'parent' => $voucher->parent ? array_merge($voucher->parent->only([
                'identity_address', 'fund_id',
            ]), [
                'created_at' => $voucher->parent->created_at_string
            ]) : null,
            'physical_card' => $physical_cards ? $physical_cards->only(['id', 'code']) : false,
            'product_vouchers' => $this->getProductVouchers($voucher->product_vouchers),
            'transactions' => VoucherTransactionResource::collection($voucher->transactions),
        ]);
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    public function getBaseFields(Voucher $voucher): array {
        if ($voucher->type === 'regular') {
            $amount = $voucher->amount_available_cached;
            $offices = $voucher->fund->provider_organizations_approved->pluck('offices')->flatten();
            $productResource = null;
        } elseif ($voucher->type === 'product') {
            $amount = $voucher->amount;
            $offices = $voucher->product->organization->offices;
            $productResource = array_merge($voucher->product->only([
                'id', 'name', 'description', 'price', 'old_price',
                'total_amount', 'sold_amount', 'product_category_id',
                'organization_id'
            ]), [
                'product_category' => $voucher->product->product_category,
                'expire_at' => $voucher->product->expire_at->format('Y-m-d'),
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicWithPrivateResource($voucher->product->organization),
            ]);
        } else {
            exit(abort("Unknown voucher type!", 403));
        }

        return [
            'amount' => currency_format($amount),
            'offices' => OfficeResource::collection($offices),
            'product' => $productResource,
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    public function getFundResource(Fund $fund): array {
        return array_merge($fund->only([
            'id', 'name', 'state', 'type',
        ]), [
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => new MediaCompactResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d H:i'),
            'start_date_locale' => format_datetime_locale($fund->start_date),
            'end_date' => $fund->end_date->format('Y-m-d H:i'),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationBasicWithPrivateResource($fund->organization),
            'allow_physical_cards' => $fund->fund_config->allow_physical_cards,
        ]);
    }

    /**
     * @param Voucher[]|Collection|null $product_vouchers
     * @return Voucher[]|Collection|null
     */
    private function getProductVouchers($product_vouchers) {
        return $product_vouchers ? $product_vouchers->map(static function(Voucher $product_voucher) {
            return array_merge($product_voucher->only([
                'identity_address', 'fund_id', 'returnable',
            ]), [
                'created_at' => $product_voucher->created_at_string,
                'created_at_locale' => $product_voucher->created_at_string_locale,
                'address' => $product_voucher->token_with_confirmation->address,
                'amount' => currency_format($product_voucher->amount),
                'date' => $product_voucher->created_at->format('M d, Y'),
                'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                'timestamp' => $product_voucher->created_at->timestamp,
                'product' => self::getProductDetails($product_voucher),
            ]);
        })->values() : null;
    }

    /**
     * @param Voucher $product_voucher
     * @return array
     */
    private static function getProductDetails(
        Voucher $product_voucher
    ): array {
        return array_merge($product_voucher->product->only([
            'id', 'name', 'description', 'total_amount',
            'sold_amount', 'product_category_id', 'organization_id'
        ]), $product_voucher->fund->isTypeBudget() ? [
            'price' => currency_format($product_voucher->product->price),
            'old_price' => currency_format($product_voucher->product->old_price),
        ] : []);
    }
}
