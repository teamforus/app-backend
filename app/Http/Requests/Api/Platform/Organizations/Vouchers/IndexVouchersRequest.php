<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;

/**
 * Class IndexVouchersRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class IndexVouchersRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->auth_address(), [
            'manage_vouchers'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $funds = $this->organization->funds()->pluck('funds.id');

        return [
            'per_page'          => 'numeric|between:1,100',
            'fund_id'           => 'nullable|exists:funds,id|in:' . $funds->join(','),
            'granted'           => 'nullable|boolean',
            'amount_min'        => 'nullable|numeric',
            'amount_max'        => 'nullable|numeric',
            'from'              => 'nullable|date_format:Y-m-d',
            'to'                => 'nullable|date_format:Y-m-d',
            'type'              => 'required|in:fund_voucher,product_voucher',
            'unassigned'        => 'nullable|boolean',
            'source'            => 'required|in:all,user,employee',
            'export_type'       => 'nullable|in:pdf,xls,csv,png',
            'sort_by'           => 'nullable|in:amount,expire_at,created_at',
            'state'             => 'nullable|in:' . implode(',', Voucher::STATES),
            'sort_order'        => 'nullable|in:asc,desc',
            'export_only_data'  => 'nullable|boolean',
            'q'                 => 'nullable|string|max:100',
            'email'             => 'nullable|string|max:100',
            'bsn'               => 'nullable|string|max:100',
            'in_use'            => 'nullable|boolean',
            'expired'           => 'nullable|boolean',
        ];
    }
}
