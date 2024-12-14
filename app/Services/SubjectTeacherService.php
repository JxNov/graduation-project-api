<?php

namespace App\Services;

use App\Http\Resources\SubjectTeacherCollection;
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
            // Lấy các môn học theo slug
            $subjects = Subject::whereIn('slug', $data['subjectSlug'])->get();
            if ($subjects->isEmpty()) {
                throw new Exception('Một hoặc nhiều môn học không tồn tại hoặc đã bị xóa');
            }

            // Lấy role của giáo viên
            $roleTeacher = Role::where('slug', 'teacher')->first();
            if (!$roleTeacher) {
                throw new Exception('Không tìm thấy vai trò giáo viên');
            }

            // Kiểm tra xem username có phải là một mảng không
            if (!is_array($data['username'])) {
                $data['username'] = [$data['username']];
            }

            // Lấy danh sách các giáo viên
            $users = User::whereIn('username', $data['username'])
                ->whereHas('roles', function ($query) use ($roleTeacher) {
                    $query->where('role_id', $roleTeacher->id);
                })->get();

            if ($users->isEmpty()) {
                throw new Exception('Không có giáo viên nào với username này hoặc không phải là giáo viên');
            }

            $successfulUsers = [];

            // Duyệt qua từng giáo viên và từng môn học
            foreach ($users as $user) {
                foreach ($subjects as $subject) {
                    // Kiểm tra xem giáo viên đã dạy môn học này chưa
                    if ($user->subjects()->where('subject_id', $subject->id)->exists()) {
                        // Nếu giáo viên đã dạy môn học này rồi, báo lỗi
                        throw new Exception("Giáo viên {$user->name} đã dạy môn {$subject->name} rồi");
                    }

                    // Gán môn học cho giáo viên nếu chưa có
                    $user->subjects()->syncWithoutDetaching([
                        $subject->id => ['created_at' => Carbon::now()],
                    ]);


                    $pivot = $user->subjects()->where('subject_id', $subject->id)->first()->pivot ?? null;
                    if ($pivot) {
                        $successfulUsers[] = $pivot;
                    }
                }
            }

            return $successfulUsers;
        });
    }







    public function update(array $data, $username)
    {
        return DB::transaction(function () use ($data, $username) {
            // Lấy giáo viên theo username
            $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();
            if (!$roleTeacher) {
                throw new Exception('Vai trò giáo viên không tồn tại');
            }

            // Tìm người dùng có username và vai trò 'teacher'
            $user = User::where('username', $username)
                ->whereHas('roles', function ($query) use ($roleTeacher) {
                    $query->where('role_id', $roleTeacher->id);
                })->first();

            if (!$user) {
                throw new Exception('Username này không phải là giáo viên');
            }

            // Chuyển subjectSlug thành subject_id
            $subjectIds = [];
            foreach ($data['subjectSlugs'] as $slug) {
                $subject = Subject::where('slug', $slug)->first();
                if (!$subject) {
                    throw new Exception('Môn học với slug "' . $slug . '" không tồn tại.');
                }
                $subjectIds[] = $subject->id;
            }

            $user->subjects()->sync($subjectIds);

            return [
                'name' => $user->name,
                'username' => $user->username,
                'image' => $user->image,
                'dateOfBirth' => $user->date_of_birth,
                'gender' => $user->gender,
                'address' => $user->address,
                'phoneNumber' => $user->phone_number,
                'email' => $user->email,
                'subjects' => $user->subjects->pluck('name'),
            ];
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
