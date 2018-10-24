<?php

namespace App\Http\Requests\Api;

use App\Rules\IdentityRecordsExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class IdentityAuthorizationEmailTokenRequest extends FormRequest
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
            'primary_email' => [
                'required',
                new IdentityRecordsExistsRule('primary_email')
            ],
            'source' => [
                'required',
                'in:' . collect(
                    config('forus.front_ends')
                )->keys()->implode(',')
            ]
        ];
    }
}
