<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyFeeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'monthly_fee_id', 'billing_concept_id', 'description', 'amount', 'is_late_fee',
        'source_period_year', 'source_period_month',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_late_fee' => 'boolean'];
    }

    public function monthlyFee(): BelongsTo
    {
        return $this->belongsTo(MonthlyFee::class);
    }

    public function billingConcept(): BelongsTo
    {
        return $this->belongsTo(BillingConcept::class);
    }
}
