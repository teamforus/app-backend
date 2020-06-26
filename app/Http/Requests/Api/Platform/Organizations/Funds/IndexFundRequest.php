<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'q' => 'nullable|string|max:100',
            'tag' => 'nullable|string|exists:tags,key',
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'numeric|between:1,100',
            'organization_id' => 'nullable|exists:organizations,id',
            'implementation_id' => 'nullable|exists:implementations,id',
        ];
    }
}