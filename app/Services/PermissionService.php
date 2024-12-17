<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function createPermission($data)
    {
        DB::beginTransaction();

        try {
            $values = is_string($data) ? [$data] : $data;
            $existingPermissions = Permission::withTrashed()->whereIn('value', $values)->pluck('value')->toArray();

            $newValues = array_diff($values, $existingPermissions);

            if (empty($newValues)) {
                throw new \Exception('Quyền đã tồn tại.', Response::HTTP_CONFLICT);
            }

            $newPermissions = array_map(function ($value) {
                return [
                    'value' => $value,
                    'slug' => Str::slug($value),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }, $newValues);

            $permission = Permission::insert($newPermissions);

            if (!$permission) {
                throw new \Exception('Tạo quyền thất bại.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            DB::commit();

            return Permission::whereIn('value', $newValues)->get(['id', 'value', 'slug']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deletePermission($slug)
    {
        DB::beginTransaction();

        try {
            $permission = Permission::where('slug', $slug)->first();

            if (!$permission) {
                throw new \Exception('Quyền không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            $permission->delete();

            DB::commit();

            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
