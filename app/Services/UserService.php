<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index()
    {
        try {
            $users = User::with('roles')->get();

            if ($users->isEmpty()) {
                throw new \Exception('Không có người dùng nào', Response::HTTP_NOT_FOUND);
            }

            return $users->map(function ($user) {
                return [
                    'name' => $user->name,
                    'username' => $user->username,
                    'image' => $user->image,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'roles' => $user->roles->pluck('name'),
                ];
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getUserRoles($username): array
    {
        try {
            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user = User::select('username', 'email')->find($user->id);
            $roles = Role::whereHas('users', function ($query) use ($username) {
                $query->where('username', $username);
            })->select('name')->get();

            return [
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $roles->pluck('name'),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function assignRoles($username, $rolesSlug)
    {
        DB::beginTransaction();

        try {
            $role = Role::where('slug', $rolesSlug)->first();

            if (!$role) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $roles = $this->getReduceRoles($rolesSlug);

            $user->roles()->sync($roles);

            DB::commit();

            $user = User::select('username', 'email')->find($user->id);
            $user->roles = Role::select('name')->whereIn('id', array_keys($roles))->get();
            $user->roles = $user->roles->pluck('name');

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revokeRoles($username)
    {
        DB::beginTransaction();

        try {
            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user->roles()->detach();

            DB::commit();

            return User::select('username', 'email')->find($user->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUserPermissions($username): array
    {
        try {
            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user = User::select('username', 'email')->find($user->id);
            $permissions = Permission::whereHas('users', function ($query) use ($username) {
                $query->where('username', $username);
            })->select('value')->get();

            return [
                'username' => $user->username,
                'email' => $user->email,
                'permissions' => $permissions->pluck('value'),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function assignPermissions($username, $permissionsSlug)
    {
        DB::beginTransaction();

        try {
            $permission = Permission::where('slug', $permissionsSlug)->first();

            if (!$permission) {
                throw new \Exception('Permission không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $permissions = $this->getReducePermissions($permissionsSlug);

            $user->permissions()->sync($permissions);

            DB::commit();

            $user = User::select('username', 'email')->find($user->id);
            $user->permissions = Permission::select('value')->whereIn('id', array_keys($permissions))->get();
            $user->permissions = $user->permissions->pluck('value');

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revokePermissions($username)
    {
        DB::beginTransaction();

        try {
            $user = User::where('username', $username)->first();

            if (!$user) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $user->permissions()->detach();

            DB::commit();

            return User::select('username', 'email')->find($user->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function assignRolesAndPermissions($usersReq, $rolesReq, $permissionsReq)
    {
        DB::beginTransaction();

        try {
            $usersReq = is_string($usersReq) ? [$usersReq] : $usersReq;

            $users = User::whereIn('email', $usersReq)->get();

            if ($users->isEmpty()) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $roles = Role::where('slug', $rolesReq)->first();

            if (!$roles) {
                throw new \Exception('Role không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $permissions = Permission::where('value', $permissionsReq)->first();

            if (!$permissions) {
                throw new \Exception('Permission không tồn tại', Response::HTTP_NOT_FOUND);
            }

            $roles = $this->getReduceRoles($rolesReq);
            $permissions = $this->getReducePermissionsValue($permissionsReq);

            foreach ($users as $user) {
                $user->roles()->sync($roles);
                $user->permissions()->sync($permissions);
            }

            DB::commit();

            $user = User::with('roles', 'permissions')->whereIn('email', $usersReq)->get();

            return $user->map(function ($user) {
                return [
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->permissions->pluck('value'),
                ];
            });
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revokeRolesAndPermissions($username)
    {
        DB::beginTransaction();

        try {
            $username = is_string($username) ? [$username] : $username;

            $users = User::whereIn('username', $username)->get();

            if ($users->isEmpty()) {
                throw new \Exception('User không tồn tại', Response::HTTP_NOT_FOUND);
            }

            foreach ($users as $user) {
                $user->roles()->detach();
                $user->permissions()->detach();
            }

            DB::commit();

            return User::select('username', 'name', 'email')->whereIn('username', $username)->get();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getReduceRoles($rolesSlug): array
    {
        $roles = is_string($rolesSlug) ? [$rolesSlug] : $rolesSlug;

        $roleIds = [];

        foreach ($roles as $role) {
            $role = Role::where('slug', $role)->first();

            $roleIds[$role->id] = [
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $roleIds;
    }

    private function getReducePermissions($permissionsSlug): array
    {
        $permissions = is_string($permissionsSlug) ? [$permissionsSlug] : $permissionsSlug;

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $permission = Permission::where('slug', $permission)->first();

            $permissionIds[$permission->id] = [
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $permissionIds;
    }

    private function getReducePermissionsValue($permissionsValue): array
    {
        $permissions = is_string($permissionsValue) ? [$permissionsValue] : $permissionsValue;

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $permission = Permission::where('value', $permission)->first();

            $permissionIds[$permission->id] = [
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $permissionIds;
    }

    public function updateUser($data, $username)
    {
        return DB::transaction(function () use ($data, $username) {
            $user = User::where('username', $username)->firstOrFail();
            $imageFileNames = [];
            $firebase = app('firebase.storage');
            $storage = $firebase->getBucket();

            if (isset($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    $fileName = "{$username}_image" . ($index + 1) . ".png";
                    $imageFileNames[] = $fileName;
                    $firebasePath = "images/{$username}/{$fileName}";
                    $storage->upload(
                        file_get_contents($image->getRealPath()),
                        [
                            'name' => $firebasePath
                        ]
                    );
                }

                $imagesJson = json_encode($imageFileNames);

                $user->update([
                    'image' => $imagesJson,
                ]);
            }

            if (isset($data['password'])) {
                $password = $data['password'];
            } else {
                $password = $user->password;
            }

            $user->update([
                'password' => Hash::make($password),
            ]);

            return $user;
        });
    }
}
