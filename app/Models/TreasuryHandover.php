<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryHandover extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'condominium_board_id', 'type', 'period_starts_on', 'period_ends_on',
        'delivered_by_user_id', 'received_by_user_id', 'opening_balance', 'income_total',
        'expense_total', 'system_balance', 'bank_balance', 'cash_balance', 'delivered_amount',
        'received_amount', 'difference_amount', 'handover_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_on' => 'date',
            'period_ends_on' => 'date',
            'opening_balance' => 'decimal:2',
            'income_total' => 'decimal:2',
            'expense_total' => 'decimal:2',
            'system_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
            'cash_balance' => 'decimal:2',
            'delivered_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'handover_date' => 'date',
        ];
    }
}
