<?php

namespace App\Domain\OidcClients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $oidc_client_id
 * @property string $redirect_uri
 * @property string $redirect_uri_hash
 */
final class OidcRedirectUri extends Model
{
    protected $fillable = [
        'oidc_client_id',
        'redirect_uri',
        'redirect_uri_hash',
    ];

    protected function casts(): array
    {
        return [
            'oidc_client_id' => 'int',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(OidcClient::class, 'oidc_client_id');
    }
}
