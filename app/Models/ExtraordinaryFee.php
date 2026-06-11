<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraordinaryFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'billing_concept_id', 'name', 'description', 'amount',
        'starts_on', 'ends_on', 'apply_to', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function billingConcept(): BelongsTo
    {
        return $this->belongsTo(BillingConcept::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'extraordinary_fee_units')->withTimestamps();
    }
}
