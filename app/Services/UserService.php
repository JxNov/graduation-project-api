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

            // Upload ảnh lên Firebase Storage
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

            // Kiểm tra mật khẩu cũ
            if (isset($data['current_password'])) {
                if (empty($data['new_password'])) {
                    throw new \Exception('Vui lòng nhập mật khẩu mới.');
                }

                if (!Hash::check($data['current_password'], $user->password)) {
                    throw new \Exception('Mật khẩu hiện tại không đúng.');
                }
            }

            if (!empty($data['current_password']) && empty($data['new_password'])) {
                throw new \Exception('Vui lòng nhập mật khẩu mới.');
            }

            if (isset($data['new_password']) && isset($data['confirm_new_password'])) {
                // Kiểm tra mật khẩu mới
                if ($data['new_password'] !== $data['confirm_new_password']) {
                    throw new \Exception('Mật khẩu mới không khớp với xác nhận mật khẩu.');
                }

                // Cập nhật mật khẩu mới
                $user->update([
                    'password' => Hash::make($data['new_password']),
                ]);
            }

            return $user;
        });
    }

    public function createnewUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $existingUsernames = User::pluck('username')->toArray();

            $data['username'] = $this->generateUsername($data['name'], $existingUsernames);
            $data['email'] = $this->generateEmail($data['username']);
            $data['password'] = Hash::make(env('PASSWORD_DEFAULT'));

            $newUser = User::create($data);

            return $newUser;
        });
    }

    public function deleteUser($username)
    {
        return DB::transaction(function () use ($username) {
            $user = User::where('username', $username)->first();

            if ($user === null) {
                throw new \Exception('Người dùng không tồn tại hoặc đã bị xóa');
            }

            $user->delete();

            return $user;
        });
    }

    public function generateUsername($fullName, $existingUsernames)
    {
        $cleanedName = $this->removeAccents($fullName);

        $nameParts = explode(" ", $cleanedName);

        $firstName = strtolower(array_pop($nameParts));
        $lastName = strtolower(array_shift($nameParts));
        $lastNameInitial = substr($lastName, 0, 1);

        $middleNameInitials = '';
        foreach ($nameParts as $middleName) {
            if (!empty($middleName)) {
                $middleNameInitials .= strtolower(substr($middleName, 0, 1));
            }
        }

        $usernameBase = $firstName . $lastNameInitial . $middleNameInitials;

        $username = $usernameBase . 'ps' . rand(10000, 99999);
        while (in_array($username, $existingUsernames)) {
            $username = $usernameBase . 'ps' . rand(10000, 99999);
        }

        return $username;
    }

    public function generateEmail($username)
    {
        return $username . '@tech4school.edu.vn';
    }
    public function forgotPassword($usernames){
        return DB::transaction(function () use ($usernames) {
            $users = User::whereIn('username', $usernames)->get();
            foreach ($users as $user) {
                $user->update([
                    'password' => Hash::make(env('PASSWORD_DEFAULT')), 
                ]);
            }
            return $users;
        });
    }
    private function removeAccents($string)
    {
        $accents = [
            'à',
            'á',
            'ạ',
            'ả',
            'ã',
            'â',
            'ầ',
            'ấ',
            'ậ',
            'ẩ',
            'ẫ',
            'ă',
            'ằ',
            'ắ',
            'ặ',
            'ẳ',
            'ẵ',
            'è',
            'é',
            'ẹ',
            'ẻ',
            'ẽ',
            'ê',
            'ề',
            'ế',
            'ệ',
            'ể',
            'ễ',
            'ì',
            'í',
            'ị',
            'ỉ',
            'ĩ',
            'ò',
            'ó',
            'ọ',
            'ỏ',
            'õ',
            'ô',
            'ồ',
            'ố',
            'ộ',
            'ổ',
            'ỗ',
            'ơ',
            'ờ',
            'ớ',
            'ợ',
            'ở',
            'ỡ',
            'ù',
            'ú',
            'ụ',
            'ủ',
            'ũ',
            'ư',
            'ừ',
            'ứ',
            'ự',
            'ử',
            'ữ',
            'ỳ',
            'ý',
            'ỵ',
            'ỷ',
            'ỹ',
            'À',
            'Á',
            'Ạ',
            'Ả',
            'Ã',
            'Â',
            'Ầ',
            'Ấ',
            'Ậ',
            'Ẩ',
            'Ẫ',
            'Ă',
            'Ằ',
            'Ắ',
            'Ặ',
            'Ẳ',
            'Ẵ',
            'È',
            'É',
            'Ẹ',
            'Ẻ',
            'Ẽ',
            'Ê',
            'Ề',
            'Ế',
            'Ệ',
            'Ể',
            'Ễ',
            'Ì',
            'Í',
            'Ị',
            'Ỉ',
            'Ĩ',
            'Ò',
            'Ó',
            'Ọ',
            'Ỏ',
            'Õ',
            'Ô',
            'Ồ',
            'Ố',
            'Ộ',
            'Ổ',
            'Ỗ',
            'Ơ',
            'Ờ',
            'Ớ',
            'Ợ',
            'Ở',
            'Ỡ',
            'Ù',
            'Ú',
            'Ụ',
            'Ủ',
            'Ũ',
            'Ư',
            'Ừ',
            'Ứ',
            'Ự',
            'Ử',
            'Ữ',
            'Ỳ',
            'Ý',
            'Ỵ',
            'Ỷ',
            'Ỹ',
            'Đ',
            'đ'
        ];

        $noAccents = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'y',
            'y',
            'y',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'Y',
            'Y',
            'Y',
            'Y',
            'D',
            'd'
        ];

        return str_replace($accents, $noAccents, $string);
    }

    private function cleanString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
