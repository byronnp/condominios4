<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementImportRow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_statement_import_id', 'transaction_date', 'reference', 'voucher_number', 'description',
        'amount', 'direction', 'raw_data', 'matched_type', 'matched_id', 'match_status',
        'difference_amount', 'notes',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2', 'difference_amount' => 'decimal:2', 'raw_data' => 'array'];
    }
}
