<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominiumAccountOpeningBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'condominium_payment_method_id', 'opening_balance', 'opened_on',
        'registered_by_user_id', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'opened_on' => 'date', 'is_active' => 'boolean'];
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(CondominiumPaymentMethod::class, 'condominium_payment_method_id');
    }
}
