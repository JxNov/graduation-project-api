<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentRoleService
{

    public function store($username)
{
    return DB::transaction(function () use ($username) {
        // Lấy thông tin học sinh theo username
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return [
                'status' => false,
                'message' => 'Học sinh không tồn tại.'
            ];
        }

        // Lấy vai trò 'student'
        $role = Role::where('slug', 'student')->first();

        if (!$role) {
            return [
                'status' => false,
                'message' => 'Vai trò student không tồn tại.'
            ];
        }

        // Kiểm tra nếu học sinh đã có vai trò student
        $userHasRole = $user->roles()->where('slug', $role->slug)->exists();

        if ($userHasRole) {
            return [
                'status' => false,
                'message' => 'User ' . $user->name . ' đã có vai trò student.'
            ];
        }

        // Gán vai trò cho học sinh
        $user->roles()->attach($role->id);

        return [
            'status' => true,
            'message' => 'Gán vai trò thành công cho học sinh ' . $user->name
        ];
    });
}

public function update($data, $username)
{
    return DB::transaction(function () use ($data, $username) {
        // Tìm người dùng theo username
        $user = User::where('username', $username)->firstOrFail();

        // Lấy role của người dùng 
        $role = Role::where('slug', $data['slugRole'])->firstOrFail();

        // Xóa bản ghi cũ và thêm lại cho user đó 1 role mới
        $user->roles()->sync([$role->id]);

        return [
            'status' => true,
            'message' => 'Người dùng đã có một quyền mới là ' . $role->name,
        ];
    });
}


public function destroy($username, $slugRole)
{
    return DB::transaction(function () use ($username, $slugRole) {
        // Tìm người dùng theo username
        $user = User::where('username', $username)->firstOrFail();

        // Lấy role dựa trên slug
        $role = Role::where('slug', $slugRole)->firstOrFail();

        // Kiểm tra xem user có role cần xóa hay không
        if ($user->roles()->where('role_id', $role->id)->exists()) {
            // Cập nhật deleted_at trên bản ghi pivot cho role cụ thể
            $user->roles()->updateExistingPivot($role->id, ['deleted_at' => now()]);
            
            return [
                'status' => true,
                'message' => 'Xoá quyền thành công',
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Người dùng không có quyền này',
            ];
        }
    });
}

}
