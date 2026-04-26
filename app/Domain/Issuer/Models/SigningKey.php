<?php

namespace App\Domain\Issuer\Models;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $kid
 * @property string $algorithm
 * @property array<string, mixed> $public_jwk
 * @property string $public_key_pem
 * @property string $private_key_encrypted
 * @property string $status
 * @property Carbon|null $activated_at
 * @property Carbon|null $retired_at
 * @property-read Collection<int, ApiToken> $apiTokens
 */
final class SigningKey extends Model
{
    protected $fillable = [
        'kid',
        'algorithm',
        'public_jwk',
        'public_key_pem',
        'private_key_encrypted',
        'status',
        'activated_at',
        'retired_at',
    ];

    protected $hidden = [
        'private_key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'public_jwk' => 'array',
            'activated_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class, 'signing_key_id');
    }

    public function isActive(): bool
    {
        return $this->status === SigningKeyStatus::Active->value;
    }
}
