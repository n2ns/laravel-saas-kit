<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSessionToken extends Model
{
    protected $fillable = [
        'user_session_id',
        'access_token_id',
        'refresh_token_id',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'user_session_id');
    }
}
