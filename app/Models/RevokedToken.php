<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevokedToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'jti',
        'user_id',
        'auth_session_id',
        'expires_at',
        'revoked_at',
        'revoked_by_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authSession(): BelongsTo
    {
        return $this->belongsTo(AuthSession::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }
}
