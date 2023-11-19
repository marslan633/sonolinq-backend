<?php

namespace App\Http\Requests\Api\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'full_name' => ['string'],
            'email' => ['email',Rule::unique('clients')->whereNull('deleted_at')->ignore($this->id)],
            'phone_number' => ['sometimes', 'string'],
            'referrer_id' => ['string'],
            'company_name' => ['string', 'max:255'],
            'register_no' => ['string', 'max:255'],
            'is_vat' => ['boolean'],
            'reg_no_letter' => ['sometimes', 'file', 'mimes:png,jpg,jpeg'],
            'personal_director_id' => ['sometimes', 'file', 'mimes:png,jpg,jpeg'],
            'prove_of_address' => ['sometimes', 'file', 'mimes:png,jpg,jpeg'],
            'personal_address' => ['string'],
            'parcel_return_address' => ['string'],
            'status' => ['string']
        ];
    }

      /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'   => false,
            'status_code'   => 400,
            'message'   => 'Validation errors',
            'data'      => $validator->errors(),
        ], 200));
    }
}
