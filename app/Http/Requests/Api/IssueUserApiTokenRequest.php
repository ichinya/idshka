<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

final class IssueUserApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['required', 'string', 'max:40', 'exists:sites,id'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('[FIX:issuer-validation] issue token request validation failed', [
            'request_id' => $this->attributes->get('request_id'),
            'user_id' => $this->user()?->getAuthIdentifier(),
            'fields' => array_keys($validator->errors()->toArray()),
        ]);

        throw new HttpResponseException(response()->json([
            'error' => 'validation_failed',
            'message' => 'The given data was invalid.',
            'request_id' => $this->attributes->get('request_id'),
            'fields' => $validator->errors()->toArray(),
        ], 422));
    }
}
