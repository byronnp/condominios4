<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGatewayTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_order_id', 'gateway', 'gateway_transaction_id', 'gateway_status', 'amount',
        'authorization_code', 'reference', 'voucher_number', 'raw_response', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'raw_response' => 'array', 'confirmed_at' => 'datetime'];
    }
}
