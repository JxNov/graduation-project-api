<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted()
    {
        static::deleting(function ($role) {
            if ($role->permissions->isNotEmpty()) {
                $role->permissions()->updateExistingPivot($role->permissions->pluck('id'), ['deleted_at' => now()]);
            }
        });

        static::restoring(function ($role) {
            if ($role->permissions->isNotEmpty()) {
                $role->permissions()->updateExistingPivot($role->permissions->pluck('id'), ['deleted_at' => null]);
            }
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}
