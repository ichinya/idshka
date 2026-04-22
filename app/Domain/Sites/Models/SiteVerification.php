<?php

namespace App\Domain\Sites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $site_id
 * @property string $method
 * @property string $token
 * @property string $status
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $last_error
 */
final class SiteVerification extends Model
{
    protected $fillable = [
        'site_id',
        'method',
        'token',
        'expires_at',
        'verified_at',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
