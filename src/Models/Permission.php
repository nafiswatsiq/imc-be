<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $page_id
 * @property string $action
 * @property string $name
 */
class Permission extends Model
{
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    public $timestamps = false;

    protected $table = 'permissions';

    protected $fillable = ['page_id', 'action', 'name'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')
            ->withPivot('effect');
    }
}
