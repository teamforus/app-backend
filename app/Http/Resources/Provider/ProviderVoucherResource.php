<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

class ProviderVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Voucher $voucher */
        $voucher = $this->resource;
        $identityAddress = request()->get('identity');

        if ($voucher->product) {
            return $this->productVoucher($voucher);
        }

        return $this->regularVoucher($identityAddress, $voucher);
    }

    /**
     * Transform the resource into an array.
     *
     * @param string $identityAddress
     * @param Voucher $voucher
     * @return mixed
     */
    private function regularVoucher(
        string $identityAddress,
        Voucher $voucher
    ) {
        $amountLeft = $voucher->amount_available;
        $voucherOrganizations = $voucher->fund->provider_organizations_approved();

        $allowedOrganizations = Organization::queryByIdentityPermissions(
            $identityAddress, 'scan_vouchers'
        )->whereIn('id', $voucherOrganizations->pluck(
            'organizations.id'
        ))->get();

        $allowedProductCategories = $voucher->fund->product_categories;
        $allowedProducts = Product::query()->whereIn(
            'organization_id', $allowedOrganizations->pluck('id')
        )->where('sold_out', '=', false)->whereIn(
            'product_category_id', $allowedProductCategories->pluck('id')
        )->where('price', '<=', $amountLeft)->where(
            'expire_at', '>', date('Y-m-d')
        )->get();

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'type' => 'regular',
            'amount' => currency_format($amountLeft),
            'fund' => collect($voucher->fund)->only([
                'id', 'name', 'state'
            ])->merge([
                'organization' => new OrganizationBasicResource(
                    $voucher->fund->organization
                ),
                'logo' => new MediaCompactResource($voucher->fund->logo)
            ]),
            'allowed_organizations' => collect(
                $allowedOrganizations
            )->map(function($organization) {
                return collect($organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($organization->logo)
                ]);
            }),
            'allowed_product_categories' => ProductCategoryResource::collection(
                $allowedProductCategories
            ),
            'allowed_products' => collect($allowedProducts)->map(function($product) {
                /** @var Product $product */
                return collect($product)->only([
                    'id', 'name', 'description', 'total_amount', 'sold_amount',
                    'product_category_id', 'organization_id',
                ])->merge([
                    'price' => currency_format($product->price),
                    'old_price' => currency_format($product->old_price),
                    'photo' => new MediaCompactResource($product->photo),
                    'product_category' => new ProductCategoryResource(
                        $product->product_category
                    )
                ]);
            }),
            'product_vouchers' => $voucher->product_vouchers ? collect(
                $voucher->product_vouchers
            )->filter(function($product_voucher) {
                return !$product_voucher->used;
            })->map(function($product_voucher) {
                /** @var Voucher $product_voucher */
                return collect($product_voucher)->only([
                    'identity_address', 'fund_id', 'created_at',
                    'created_at_locale',
                ])->merge([
                    'address' => $product_voucher->tokens->where(
                        'need_confirmation', 1)->first()->address,
                    'amount' => currency_format(
                        $product_voucher->type == 'regular' ?
                            $product_voucher->amount_available_cached :
                            $product_voucher->amount
                    ),
                    'date' => $product_voucher->created_at->format('M d, Y'),
                    'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                    'timestamp' => $product_voucher->created_at->timestamp,
                    'product' => collect($product_voucher->product)->only([
                        'id', 'name', 'description', 'total_amount',
                        'sold_amount', 'product_category_id', 'organization_id',
                    ])->merge([
                        'price' => currency_format($product_voucher->product->price),
                        'old_price' => currency_format($product_voucher->product->old_price),
                        'photo' => new MediaResource($product_voucher->product->photo),
                    ])
                ]);
            })->values() : null,
        ])->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param Voucher $voucher
     * @return mixed
     */
    private function productVoucher(
        Voucher $voucher
    ) {
        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'type' => 'product',
            'product' => collect($voucher->product)->only([
                'id', 'name', 'description', 'total_amount', 'sold_amount',
                'product_category_id', 'organization_id'
            ])->merge([
                'price' => currency_format($voucher->product->price),
                'old_price' => currency_format($voucher->product->old_price),
                'photo' => new MediaResource(
                    $voucher->product->photo
                ),
                'organization' => new OrganizationBasicResource(
                    $voucher->product->organization
                ),
            ])->toArray(),
        ])->toArray();
    }
}
