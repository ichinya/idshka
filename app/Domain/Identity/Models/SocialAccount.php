<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property string|null $email
 * @property string|null $name
 * @property string|null $avatar_url
 * @property string|null $access_token_encrypted
 * @property string|null $refresh_token_encrypted
 * @property Carbon|null $expires_at
 */
final class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'name',
        'avatar_url',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
