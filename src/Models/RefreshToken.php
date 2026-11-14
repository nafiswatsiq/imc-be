<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 */
class RefreshToken extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'refresh_tokens';

    protected $fillable = ['user_id', 'token_hash', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
