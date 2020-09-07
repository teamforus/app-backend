<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderVoucherTransactionEmployeeResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherTransactionEmployeeResource extends JsonResource
{
    public static $load = [
        'voucher.fund.logo',
        'provider.logo',
        'product.photo',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;

        return array_merge($transaction->only([
            "id", "organization_id", "product_id", "address", "state",
        ]), [
            'created_at' => $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : null,
            'created_at_locale' => format_datetime_locale($transaction->created_at),
            'updated_at_locale' => format_datetime_locale($transaction->updated_at),

            'amount' => currency_format($transaction->amount),
            "organization" => array_merge($transaction->provider->only([
                "id", "name"
            ]), [
                'logo' => new MediaResource($transaction->provider->logo),
            ]),
            "product" => $transaction->product ? array_merge($transaction->product->only([
                "id", "name", "organization_id",
            ]), [
                'photo' => new MediaResource($transaction->product->photo),
            ]) : null,
            "fund" => array_merge($transaction->voucher->fund->only([
                "id", "name", "organization_id",
            ]), [
                'logo' => new MediaResource($transaction->voucher->fund->logo),
                "organization" => array_merge($transaction->voucher->fund->organization->only([
                    "id", "name"
                ]), [
                    'logo' => new MediaResource($transaction->voucher->fund->organization->logo),
                ]),
            ]),
        ]);
    }
}