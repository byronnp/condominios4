<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitAccountMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'unit_id', 'payment_id', 'monthly_fee_id', 'type', 'amount', 'balance_after', 'description',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
