<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitAliquot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'period_year',
        'period_month',
        'percentage',
        'amount',
        'starts_on',
        'ends_on',
        'status',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'percentage' => 'decimal:4',
            'amount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
