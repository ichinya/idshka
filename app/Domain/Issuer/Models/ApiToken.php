<?php

namespace App\Domain\Issuer\Models;

use App\Domain\Sites\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $site_id
 * @property int $signing_key_id
 * @property string $audience
 * @property string $jti
 * @property string $token_hash
 * @property list<string> $scopes
 * @property list<string> $permissions
 * @property Carbon $issued_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 */
final class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'site_id',
        'signing_key_id',
        'audience',
        'jti',
        'token_hash',
        'scopes',
        'permissions',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'signing_key_id' => 'int',
            'scopes' => 'array',
            'permissions' => 'array',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function signingKey(): BelongsTo
    {
        return $this->belongsTo(SigningKey::class, 'signing_key_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
