<?php

namespace App\Domain\Sites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $site_id
 * @property string $mode
 * @property \Illuminate\Support\Carbon $enabled_at
 */
final class SiteMode extends Model
{
    protected $fillable = [
        'site_id',
        'mode',
        'enabled_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
