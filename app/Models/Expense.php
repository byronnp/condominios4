<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'expense_category_id', 'condominium_payment_method_id', 'supplier_name',
        'supplier_document', 'description', 'amount', 'expense_date', 'paid_at', 'reference',
        'voucher_number', 'status', 'registered_by_user_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expense_date' => 'date', 'paid_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
