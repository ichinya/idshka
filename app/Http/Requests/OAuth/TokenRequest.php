<?php

namespace App\Http\Requests\OAuth;

use App\Domain\Issuer\Services\PkceService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grant_type' => ['required', Rule::in(['authorization_code'])],
            'client_id' => ['required', 'string', 'max:80'],
            'client_secret' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128', 'regex:'.PkceService::VERIFIER_REGEX],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('[oauth.token.validation] failed', [
            'request_id' => $this->attributes->get('request_id'),
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
