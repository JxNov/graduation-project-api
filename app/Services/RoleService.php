<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;

class RoleService
{
    public function getRoles()
    {
        try {
            $roles = Role::with('permissions')->get();

            foreach ($roles as $role) {
                $role->permissions = $role->permissions->pluck('value');
            }

            return $roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permissions' => $role->permissions,
                ];
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteRole($slug)
    {
        DB::beginTransaction();

        try {
            $role = Role::where('slug', $slug)->first();

            if (!$role) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $role->delete();

            DB::commit();

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restoreRole($name)
    {
        DB::beginTransaction();

        try {
            $role = Role::withTrashed()->where('name', $name)->first();

            if (!$role) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $role->restore();

            DB::commit();

            return Role::select('name', 'slug')->find($role->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function forceDeleteRole($slug)
    {
        DB::beginTransaction();

        try {
            $role = Role::withTrashed()->where('slug', $slug)->first();

            if (!$role) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $role->forceDelete();

            DB::commit();

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createRoleAndAttachPermissions($data, $permissionValues)
    {
        DB::beginTransaction();

        try {
            if (Role::onlyTrashed()->where('slug', Str::slug($data))->exists()) {
                throw new \Exception('Role đã bị xóa', Response::HTTP_NOT_FOUND);
            }

            $role = Role::firstOrCreate([
                'name' => $data,
                'slug' => Str::slug($data),
            ]);

            $newPermissionIds = $this->getReduce($permissionValues);

            $role->permissions()->sync($newPermissionIds);

            DB::commit();

            $role = Role::select('name', 'slug')->find($role->id);
            $permission = Permission::whereIn('id', array_keys($newPermissionIds))->get();
            $role->permissions = $permission->pluck('value');

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateRoleAndSyncPermissions($slug, $data, $permissionValues)
    {
        DB::beginTransaction();

        try {
            $role = Role::where('slug', $slug)->first();

            if (!$role) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $newPermissionIds = $this->getReduce($permissionValues);

            $role->update(['name' => $data]);
            $role->permissions()->sync($newPermissionIds);

            DB::commit();

            $role = Role::select('name', 'slug')->find($role->id);
            $permission = Permission::whereIn('id', array_keys($newPermissionIds))->get();
            $role->permissions = $permission->pluck('value');

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getReduce($permissionValues): array
    {
        $permissionValues = is_string($permissionValues) ? [$permissionValues] : $permissionValues;

        $permissionIds = [];

        foreach ($permissionValues as $permissionValue) {
            $permission = Permission::where('value', $permissionValue)->first();

            if (!$permission) {
                $permission = Permission::create([
                    'value' => $permissionValue,
                    'slug' => Str::slug($permissionValue),
                ]);
            }

            $permissionIds[$permission->id] = [
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $permissionIds;
    }
}
