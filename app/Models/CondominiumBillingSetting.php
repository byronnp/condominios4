<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominiumBillingSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'due_day', 'grace_days', 'late_fee_type', 'late_fee_value',
        'late_fee_frequency', 'apply_late_fee_automatically', 'currency', 'rounding_mode', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'due_day' => 'integer',
            'grace_days' => 'integer',
            'late_fee_value' => 'decimal:4',
            'apply_late_fee_automatically' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }
}
