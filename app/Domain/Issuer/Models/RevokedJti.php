<?php

namespace App\Domain\Issuer\Models;

use App\Domain\Sites\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $jti
 * @property int $api_token_id
 * @property int $user_id
 * @property string $site_id
 * @property string $audience
 * @property Carbon|null $expires_at
 * @property Carbon $revoked_at
 */
final class RevokedJti extends Model
{
    protected $table = 'revoked_jti';

    protected $fillable = [
        'jti',
        'api_token_id',
        'user_id',
        'site_id',
        'audience',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'api_token_id' => 'int',
            'user_id' => 'int',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'api_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
