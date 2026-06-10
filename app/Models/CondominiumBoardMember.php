<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominiumBoardMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_board_id',
        'user_id',
        'role_name',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(CondominiumBoard::class, 'condominium_board_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
