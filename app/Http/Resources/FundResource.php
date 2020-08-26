<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use Gate;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundResource
 * @property Fund $resource
 * @package App\Http\Resources
 */
class FundResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fund               = $this->resource;
        $organization       = $fund->organization;
        $sponsorCount       = $organization->employees->count();
        $validators         = $organization->employeesWithPermissionsQuery([
            'validate_records'
        ])->get();

        $providersEmployeeCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $providersEmployeeCount->reduce(static function (
            int $carry,
            Organization $organization
        ) {
            return $carry + $organization->employees->count();
        }, 0);

        if (Gate::allows('funds.showFinances', [$fund, $organization])) {
            $financialData = [
                'sponsor_count'                => $sponsorCount,
                'provider_organizations_count' => $fund->provider_organizations_approved->count(),
                'provider_employees_count'  => $providersEmployeeCount,
                'requester_count'           => $fund->vouchers->where(
                    'parent_id', '=', null
                )->count(),
                'validators_count'          => $validators->count(),
                'budget'                    => [
                    'total'     => currency_format($fund->budget_total),
                    'validated' => currency_format($fund->budget_validated),
                    'used'      => currency_format($fund->budget_used),
                    'left'      => currency_format($fund->budget_left),
                    'reserved'  => currency_format($fund->budget_reserved)
                ]
            ];
        } else {
            $financialData = [];
        }

        $data = array_merge($fund->only([
            'id', 'name', 'description', 'organization_id', 'state', 'notification_amount', 'tags'
        ]), [
            'key' => $fund->fund_config->key ?? '',
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($organization),
            'criteria' => FundCriterionResource::collection($fund->criteria),
            'formulas' => FundFormulaResource::collection($fund->fund_formulas),
            'formula_products' => $fund->fund_formula_products->pluck('product_id'),
            'fund_amount'    => $fund->amountFixedByFormula(),
            'implementation' => new ImplementationResource($fund->fund_config->implementation ?? null),
        ], $financialData);

        if ($organization->identityCan(auth()->id(), 'manage_funds')) {
            $data = array_merge($data, $fund->only([
                'default_validator_employee_id', 'auto_requests_validation',
            ]), [
                'criteria_editable' => $fund->criteriaIsEditable(),
            ]);
        }

        if ($organization->identityCan(auth()->id(), 'validate_records')) {
            $data = array_merge($data, [
                'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
                'csv_required_keys' => $fund->requiredPrevalidationKeys()->toArray()
            ]);
        }

        return $data;
    }
}
