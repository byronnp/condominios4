<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'unit_id', 'billing_responsible_user_id', 'period_year', 'period_month',
        'due_date', 'total_amount', 'paid_amount', 'balance_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function billingResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billing_responsible_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonthlyFeeItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
