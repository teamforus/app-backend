<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\Markdown\Markdown;
use Illuminate\Http\Resources\Json\Resource;

class ProductResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Product $product */
        $product = $this->resource;
        $suppliedFundIds = $product->organization->supplied_funds_approved;

        $funds = $product->product_category->funds()->whereIn(
            'funds.id', $suppliedFundIds->pluck('id')
        )->get();

        $totalAmount = $product->total_amount;
        $countReserved = $product->countReserved();
        $countSold = $product->countSold();

        $parser = new Markdown();
        $parser->enableNewlines = true;
        $htmlDescription = $parser->parse($product->description);

        return collect($product)->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id'
        ])->merge([
            'organization' => collect($product->organization)->only([
                'name', 'email', 'phone'
            ]),
            'total_amount' => $totalAmount,
            'reserved_amount' => $countReserved,
            'stock_amount' => $totalAmount - ($countReserved + $countSold),
            'price' => currency_format($product->price),
            'old_price' => currency_format($product->old_price),
            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at),
            'expired' => $product->expired,
            'funds' => $funds->map(function($fund) {
                return [
                    'logo' => new MediaResource($fund->logo),
                    'id' => $fund->id,
                    'name' => $fund->name
                ];
            }),
            'offices' => OfficeResource::collection($product->organization->offices),
            'photo' => new MediaResource(
                $product->photo
            ),
            'product_category' => new ProductCategoryResource(
                $product->product_category
            ),
            'html_description' => $htmlDescription
        ])->toArray();
    }
}
