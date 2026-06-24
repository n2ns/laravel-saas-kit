<?php

namespace App\Http\Requests\Auth;

use App\Services\Auth\AuthClientRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GoogleAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint for authentication
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validClients = app(AuthClientRegistry::class)->clientIds();

        return [
            'id_token' => 'required|string|min:100',
            'client_id' => 'required|string|max:100|in:'.implode(',', $validClients),
            'product_code' => 'required|string|max:100',
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'platform' => 'required|string|max:50',
            'app_version' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'id_token.required' => 'Google ID token is required.',
            'id_token.min' => 'Invalid Google ID token format.',
            'client_id.required' => 'Client identifier is required.',
            'client_id.in' => 'Unknown client identifier.',
            'product_code.required' => 'Product code is required.',
            'device_id.required' => 'Device identifier is required.',
            'platform.required' => 'Platform is required.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clientId = (string) $this->input('client_id');
            $productCode = (string) $this->input('product_code');

            if ($clientId !== '' && $productCode !== '' && ! app(AuthClientRegistry::class)->matchesProduct($clientId, $productCode)) {
                $validator->errors()->add('product_code', 'Product code is not allowed for this client.');
            }
        });
    }
}
