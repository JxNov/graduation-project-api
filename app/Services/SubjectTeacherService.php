<?php

namespace App\Services;

use App\Imports\StudentClassImport;
use App\Models\Classes;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SubjectTeacherService
{
    public function store(array $data)
{
    return DB::transaction(function () use ($data) {
        $subject = Subject::where('slug', $data['subjectSlug'])->first();
        if ($subject === null) {
            throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
        }

        $roleTeacher = Role::where('slug', 'teacher')->first();

        if (!is_array($data['username'])) {
            $data['username'] = [$data['username']];
        }

        $users = User::whereIn('username', $data['username'])
            ->whereHas('roles', function ($query) use ($roleTeacher) {
                $query->where('role_id', $roleTeacher->id);
            })->get();

        if ($users->isEmpty()) {
            throw new Exception('Không có giáo viên nào với username này hoặc không phải là giáo viên');
        }

        $successfulUsers = [];

        foreach ($users as $user) {
            if (!$user->subjects()->where('subject_id', $subject->id)->exists()) {
                $user->subjects()->syncWithoutDetaching([
                    $subject->id => ['created_at' => Carbon::now()],
                ]);
                // Load lại pivot để sử dụng Resource
                $successfulUsers[] = $user->subjects()->where('subject_id', $subject->id)->first()->pivot;
            }
        }

        return collect($successfulUsers);
    });
}





    public function update(array $data, $id)
{
    return DB::transaction(function () use ($data, $id) {
        $subjectTeacher = DB::table('subject_teachers')->where('id', $id)->first();
        if (!$subjectTeacher) {
            throw new Exception('Không tìm thấy bản ghi với ID đã cung cấp.');
        }
        
        // Lấy vai trò 'teacher'
        $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();
        if (!$roleTeacher) {
            throw new Exception('Vai trò giáo viên không tồn tại');
        }
        
        // Tìm người dùng có username và vai trò 'teacher'
        $user = User::where('username', $data['username'])
            ->whereHas('roles', function ($query) use ($roleTeacher) {
                $query->where('role_id', $roleTeacher->id);
            })->first();
        
        if (!$user) {
            throw new Exception('Username này không phải là giáo viên');
        }
        
        // Kiểm tra trùng lặp: xem có giáo viên khác đã dạy môn này chưa
        $duplicate = DB::table('subject_teachers')
            ->where('subject_id', $subjectTeacher->subject_id)
            ->where('teacher_id', $user->id)
            ->exists();

        if ($duplicate) {
            throw new Exception('Giáo viên này đã dạy môn này');
        }

        // Nếu không có trùng lặp, tiến hành cập nhật
        DB::table('subject_teachers')
            ->where('id', $id)
            ->update([
                'teacher_id' => $user->id,
                'updated_at' => Carbon::now(),
            ]);

        return $subjectTeacher;
    });
}


    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            // Lấy bản ghi lớp học của học sinh từ bảng `subject_teachers`
            $subjecTeacher = DB::table('subject_teachers')
                ->where('id', $id)
                ->whereNull('deleted_at') // Đảm bảo chỉ chọn bản ghi chưa bị xoá mềm
                ->first();

            if (!$subjecTeacher) {
                throw new Exception('Bản ghi không tồn tại hoặc đã bị xoá');
            }

            // Cập nhật `deleted_at` để xoá mềm bản ghi trong `subject_teachers`
            DB::table('subject_teachers')
                ->where('id', $id)
                ->update(['deleted_at' => Carbon::now()]);

            return null;
        });
    }
    public function backup($id)
    {
        return DB::transaction(function () use ($id) {
            // Lấy bản ghi lớp học của học sinh từ bảng `subject_teachers`
            $subjectTeacher = DB::table('subject_teachers')
                ->where('id', $id)
                ->whereNotNull('deleted_at') // Đảm bảo chỉ chọn bản ghi chưa bị xoá mềm
                ->first();

            if (!$subjectTeacher) {
                throw new Exception('Bản ghi không tồn tại hoặc đã bị xoá');
            }

            // Cập nhật `deleted_at` để xoá mềm bản ghi trong `subject_teachers`
            DB::table('subject_teachers')
                ->where('id', $id)
                ->update(['deleted_at' => null]);

            return $subjectTeacher;
        });
    }
    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $subjectTeacher = DB::table('subject_teachers')
                ->where('id', $id)
                ->whereNotNull('deleted_at') 
                ->first();

            if ($subjectTeacher === null) {
                throw new Exception('Không tìm thấy!');
            }

            
            DB::table('subject_teachers')
                ->where('id', $id)
                ->whereNotNull('deleted_at') 
                ->delete();

            return response()->json(['message' => 'Đã được xoá vĩnh viễn!']);
        });
    }
}
