<?php

namespace App\Http\Requests\OAuth;

use App\Contracts\Auth\OidcScopes;
use App\Domain\Issuer\Services\PkceService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class AuthorizeRequest extends FormRequest
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
            'response_type' => ['required', Rule::in(['code'])],
            'client_id' => ['required', 'string', 'max:80'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
            'scope' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'nonce' => ['required', 'string', 'max:255'],
            'code_challenge' => ['required', 'string', 'size:43', 'regex:'.PkceService::S256_CHALLENGE_REGEX],
            'code_challenge_method' => ['required', Rule::in(['S256'])],
        ];
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        $scopes = array_values(array_filter(array_unique(explode(' ', trim((string) $this->string('scope'))))));
        sort($scopes);
        $allowed = OidcScopes::all();
        $invalid = array_values(array_diff($scopes, $allowed));

        if (! in_array(OidcScopes::OPENID, $scopes, true) || $invalid !== []) {
            Log::warning('[oauth.authorize.validation] invalid_scope', [
                'request_id' => $this->attributes->get('request_id'),
                'client_id' => $this->string('client_id')->toString(),
                'invalid_scope_count' => count($invalid),
                'has_openid' => in_array(OidcScopes::OPENID, $scopes, true),
            ]);

            throw new HttpResponseException(response()->json([
                'error' => 'invalid_scope',
                'message' => 'Requested scopes are not allowed.',
                'request_id' => $this->attributes->get('request_id'),
            ], 422));
        }

        return $scopes;
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('[oauth.authorize.validation] failed', [
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
