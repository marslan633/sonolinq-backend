<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class RegisterClientRequest extends FormRequest
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
            // 'full_name' => ['required'],
            // 'email' => ['required'],
            // 'phone_number' => ['sometimes', 'string'],
            // 'referrer_id' => ['string'],
            // 'password' => ['required', 'min:8'],
            // 'terms' => ['required', 'boolean'],
            // 'company_name' => ['required', 'max:255'],
            // 'is_vat' => ['required', 'boolean'],
            // 'personal_director_id' => ['required', 'file', 'mimes:png,jpg,jpeg'],
            // 'prove_of_address' => ['required', 'file', 'mimes:png,jpg,jpeg'],
            // 'personal_address' => ['required', 'string'],
            // 'parcel_return_address' => ['required', 'string'],
            // 'url' => ['required']
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