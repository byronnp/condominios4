<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccountMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'condominium_payment_method_id', 'type', 'direction', 'amount',
        'movement_date', 'reference', 'voucher_number', 'description', 'registered_by_user_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'movement_date' => 'date'];
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(CondominiumPaymentMethod::class, 'condominium_payment_method_id');
    }
}
