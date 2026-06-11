<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementImport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id', 'condominium_payment_method_id', 'uploaded_by_user_id', 'original_file_name',
        'file_path', 'period_year', 'period_month', 'status', 'total_rows', 'matched_rows',
        'unmatched_rows', 'difference_rows',
    ];

    protected function casts(): array
    {
        return ['period_year' => 'integer', 'period_month' => 'integer'];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BankStatementImportRow::class);
    }
}
