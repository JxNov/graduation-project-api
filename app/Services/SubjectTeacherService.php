<?php

namespace App\Services;


use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
class SubjectTeacherService
{
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            
            $subjects = Subject::whereIn('slug', $data['subjectSlugs'])->get();

            if ($subjects->isEmpty()) {
                throw new Exception('Một hoặc nhiều môn học không tồn tại hoặc đã bị xóa.');
            }

           
            $roleTeacher = Role::where('slug', 'teacher')->first();
            if (!$roleTeacher) {
                throw new Exception('Không tìm thấy vai trò giáo viên.');
            }

            // Lấy danh sách giáo viên theo username và vai trò
            $teachers = User::whereIn('username', $data['usernames'])
                ->whereHas('roles', function ($query) {
                    $query->where('slug', 'teacher');
                })->get();

            if ($teachers->isEmpty()) {
                throw new Exception('Không có giáo viên nào với username này hoặc không phải là giáo viên.');
            }

            // Gán mỗi giáo viên vào tất cả các môn học
            foreach ($teachers as $teacher) {
                foreach ($subjects as $subject) {
                    // Kiểm tra nếu giáo viên đã dạy môn học đó
                    $alreadyTeaching = DB::table('subject_teachers')
                        ->where('teacher_id', $teacher->id)
                        ->where('subject_id', $subject->id)
                        ->exists();

                    if ($alreadyTeaching) {
                        throw new Exception("Giáo viên {$teacher->username} đã dạy môn {$subject->slug}.");
                    }

                    $teacher->subjects()->attach($subject->id, ['created_at' => now()]);
                }
            }

            return (object)[
                'teachersName' => $teachers->pluck('name')->all(),
                'username' => $teachers->pluck('username')->all(),
                'subjectsName' => $subjects->pluck('name')->all(),

            ];
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
