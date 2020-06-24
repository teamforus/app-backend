<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use Illuminate\Database\Eloquent\Builder;

class FundQuery
{
    /**
     * @param Builder $query
     * @return Builder
     */
    public static function whereActiveFilter(Builder $query) {
        return $query->where([
            'state' => Fund::STATE_ACTIVE
        ])->where('end_date', '>', now());
    }

    /**
     * @param Builder $query
     * @param $product_id
     * @return Builder
     */
    public static function whereProductsAreApprovedFilter(Builder $query, $product_id) {
        return $query->whereHas('providers', function(
            Builder $builder
        ) use ($product_id) {
            $builder->where(function(Builder $builder) use ($product_id) {
                $builder->whereHas('organization.products', function(
                    Builder $builder
                ) use ($product_id) {
                    $builder->whereIn('products.id', (array) $product_id);
                })->where('allow_products', true);
            });

            $builder->orWhereHas('fund_provider_products', function(
                Builder $builder
            ) use ($product_id) {
                $builder->whereIn('product_id', (array) $product_id);
            });
        });
    }

    /**
     * @param Builder $query
     * @param int|array $organization_id External validator organization id
     * @return Builder
     */
    public static function whereExternalValidatorFilter(Builder $query, $organization_id) {
        return $query->whereHas('criteria.fund_criterion_validators', function(
            Builder $builder
        ) use ($organization_id) {
            $builder->whereHas('external_validator.validator_organization', function(
                Builder $builder
            ) use ($organization_id) {
                $builder->whereIn('organizations.id', (array) $organization_id);
            });
        });
    }
}