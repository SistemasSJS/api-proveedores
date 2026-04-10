<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'avatar',
        'provider_token',
        'provider_refresh_token',
    ];

    /**
     * Relación con el usuario dueño de la cuenta social
     *
     * @return BelongsTo<User, SocialAccount>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}