<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'common_area_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'scheduled_starts_at',
        'scheduled_ends_at',
        'completed_at',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_starts_at' => 'datetime',
            'scheduled_ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'cost' => 'decimal:2',
        ];
    }

    public function commonArea(): BelongsTo
    {
        return $this->belongsTo(CommonArea::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class);
    }
}
