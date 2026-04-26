<?php

namespace App\Domain\Issuer\Models;

use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $oidc_client_id
 * @property int $user_id
 * @property string $site_id
 * @property string $code_hash
 * @property string $redirect_uri
 * @property list<string> $scopes
 * @property string $nonce
 * @property string $code_challenge
 * @property string $code_challenge_method
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 */
final class OAuthAuthorizationCode extends Model
{
    protected $table = 'oauth_authorization_codes';

    protected $fillable = [
        'oidc_client_id',
        'user_id',
        'site_id',
        'code_hash',
        'redirect_uri',
        'scopes',
        'nonce',
        'code_challenge',
        'code_challenge_method',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code_hash',
        'code_challenge',
    ];

    protected function casts(): array
    {
        return [
            'oidc_client_id' => 'int',
            'user_id' => 'int',
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(OidcClient::class, 'oidc_client_id');
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
