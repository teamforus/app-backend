<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Gate;

/**
 * Class ProductResource
 * @property Product $resource
 * @package App\Http\Resources
 */
class ProductResource extends Resource
{
    public static $load = [
        'voucher_transactions',
        'vouchers_reserved',
        'photo.presets',
        'product_category.translations',
        // 'organization.product_categories.translations',
        'organization.offices.photo.presets',
        'organization.offices.schedules',
        'organization.offices.organization',
        // 'organization.offices.organization.business_type.translations',
        'organization.offices.organization.logo.presets',
        // 'organization.offices.organization.product_categories.translations',
        'organization.supplied_funds_approved.logo',
        'organization.logo.presets',
        'organization.business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $product = $this->resource;

        $fundsQuery = FundQuery::whereActiveFilter(Fund::query());
        $fundsQuery = FundQuery::whereProductsAreApprovedFilter($fundsQuery, $product->id);

        if (Gate::allows('showFunds', [$product, $product->organization])) {
            $append = [
                'has_chats' => $product->fund_provider_chats()->exists(),
                'unseen_messages' => FundProviderChatMessage::whereIn(
                    'fund_provider_chat_id', $product->fund_provider_chats()->pluck('id')
                )->where([
                    'provider_seen' => false
                ])->count(),
            ];
        } else {
            $append = [];
        }

        return collect($product)->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id'
        ])->merge($append)->merge([
            'description_html' => resolve('markdown')->convertToHtml(
                $product->description
            ),
            'organization' => new OrganizationBasicResource(
                $product->organization
            ),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->vouchers_reserved->count(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'price' => currency_format($product->price),
            'old_price' => $product->old_price ? currency_format($product->old_price) : null,
            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => $product->deleted_at ? format_date_locale($product->deleted_at) : null,
            'deleted' => !is_null($product->deleted_at),
            'funds' => $fundsQuery->get()->map(function($fund) {
                return [
                    'logo' => new MediaResource($fund->logo),
                    'id' => $fund->id,
                    'name' => $fund->name
                ];
            })->values(),
            'offices' => OfficeResource::collection(
                $product->organization->offices
            ),
            'photo' => new MediaResource($product->photo),
            'product_category' => new ProductCategoryResource(
                $product->product_category
            )
        ])->toArray();
    }
}
