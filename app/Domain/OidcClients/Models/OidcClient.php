<?php

namespace App\Domain\OidcClients\Models;

use App\Domain\Sites\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $site_id
 * @property int $owner_user_id
 * @property string $client_id
 * @property string $client_secret_hash
 * @property string $name
 * @property Carbon|null $revoked_at
 * @property-read Site $site
 * @property-read User $owner
 * @property-read Collection<int, OidcRedirectUri> $redirectUris
 */
final class OidcClient extends Model
{
    protected $fillable = [
        'site_id',
        'owner_user_id',
        'client_id',
        'client_secret_hash',
        'name',
        'revoked_at',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'owner_user_id' => 'int',
            'revoked_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function redirectUris(): HasMany
    {
        return $this->hasMany(OidcRedirectUri::class, 'oidc_client_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
