<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['condominium_id', 'unit_id', 'user_id', 'amount', 'status', 'expires_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expires_at' => 'datetime'];
    }
}
