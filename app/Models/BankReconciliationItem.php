<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_reconciliation_id', 'payment_id', 'bank_account_movement_id', 'expense_id',
        'transaction_date', 'reference', 'voucher_number', 'description', 'bank_amount',
        'system_amount', 'difference_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'bank_amount' => 'decimal:2', 'system_amount' => 'decimal:2', 'difference_amount' => 'decimal:2'];
    }
}
