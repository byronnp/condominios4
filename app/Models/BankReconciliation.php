<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'condominium_payment_method_id', 'bank_statement_import_id', 'period_year',
        'period_month', 'bank_statement_balance', 'system_balance', 'difference_amount', 'status',
        'reconciled_by_user_id', 'reconciled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'bank_statement_balance' => 'decimal:2',
            'system_balance' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'reconciled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }
}
