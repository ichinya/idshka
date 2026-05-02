<?php

namespace App\Domain\Audit\Models;

use App\Domain\Sites\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $site_id
 * @property string $category
 * @property string $action
 * @property string $summary
 * @property array<string, mixed> $metadata
 * @property Carbon $occurred_at
 */
final class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'site_id',
        'category',
        'action',
        'summary',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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
}
