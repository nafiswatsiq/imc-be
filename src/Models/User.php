<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property int $role_id
 * @property bool $is_active
 * @property Role $role
 */
class User extends Model
{
    protected $table = 'users';

    protected $fillable = ['full_name', 'username', 'email', 'role_id', 'is_active'];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id')->withTrashed();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
            ->withPivot('effect');
    }
}
