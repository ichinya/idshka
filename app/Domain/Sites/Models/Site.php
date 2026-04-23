<?php

namespace App\Domain\Sites\Models;

use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $owner_user_id
 * @property string|null $display_name
 * @property string $domain
 * @property string $normalized_domain
 * @property string $verification_status
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property-read Collection<int, SiteMode> $modes
 * @property-read Collection<int, SiteVerification> $verifications
 */
final class Site extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_user_id',
        'display_name',
        'domain',
        'normalized_domain',
        'verification_status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'owner_user_id' => 'int',
            'verified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(SiteVerification::class, 'site_id');
    }

    public function modes(): HasMany
    {
        return $this->hasMany(SiteMode::class, 'site_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === SiteVerificationStatus::Verified->value;
    }
}
