<?php

namespace App\Services;

use App\Imports\StudentClassImport;
use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentClassService
{
    public function importStudents(array $data)
    {
        return DB::transaction(function () use ($data) {
            $file = $data['file'];
            $generationSlug = $data['generationSlug'];
            $academicYearSlug = $data['academicYearSlug'];
            $classSlug = $data['classSlug'];
            return Excel::import(new StudentClassImport($generationSlug, $academicYearSlug, $classSlug), $file);
        });
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Lấy thông tin lớp học
            $class = Classes::where('slug', $data['classSlug'])->firstOrFail();
            if ($class === null) {
                throw new Exception('Class không tồn tại hoặc đã bị xóa');
            }

            // Lấy vai trò 'student'
            $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();

            // Tìm người dùng có username và vai trò 'student'
            $user = User::where('username', $data['username'])
                ->whereHas('roles', function ($query) use ($roleStudent) {
                    $query->where('role_id', $roleStudent->id);
                })->first();
            if (!$user) {
                throw new Exception('Username này không phải là học sinh');
            }
            // Kiểm tra xem học sinh đã thuộc lớp này chưa
            $existingClass = $user->classes()->where('class_id', $class->id)->first();
            if ($existingClass) {
                throw new Exception('Học sinh đã thuộc lớp này');
            }

            // Kiểm tra xem học sinh đã tham gia lớp khác chưa
            $otherClass = $user->classes()->first();
            if ($otherClass) {
                throw new Exception('Học sinh chỉ được tham gia một lớp');
            }

            // Thêm học sinh vào lớp với mốc thời gian tạo
            $user->classes()->sync([
                $class->id => [
                    'created_at' => Carbon::now()
                ]
            ]);

            return $user;
        });
    }


    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $studentClass = DB::table('class_students')->find($id);

            $class = Classes::where('slug', $data['classSlug'])->firstOrFail();
            if ($class === null) {
                throw new Exception('Class không tồn tại hoặc đã bị xóa');
            }

            DB::table('class_students')
                ->where('id', $id)
                ->update([
                    'class_id' => $class->id,
                    'updated_at' => Carbon::now(),

                ]);

            return $studentClass;
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            // Lấy bản ghi lớp học của học sinh từ bảng `class_students`
            $studentClass = DB::table('class_students')
                ->where('student_id', $id)
                ->whereNull('deleted_at') // Đảm bảo chỉ chọn bản ghi chưa bị xoá mềm
                ->first();

            if (!$studentClass) {
                throw new Exception('Bản ghi không tồn tại hoặc đã bị xoá');
            }

            // Cập nhật `deleted_at` để xoá mềm bản ghi trong `class_students`
            DB::table('class_students')
                ->where('student_id', $id)
                ->where('class_id', $studentClass->class_id)
                ->update(['deleted_at' => Carbon::now()]);

            return null;
        });
    }
    public function backup($id)
    {
        return DB::transaction(function () use ($id) {
            // Lấy bản ghi lớp học của học sinh từ bảng `class_students`
            $studentClass = DB::table('class_students')
                ->where('student_id', $id)
                ->whereNotNull('deleted_at') // Đảm bảo chỉ chọn bản ghi chưa bị xoá mềm
                ->first();

            if (!$studentClass) {
                throw new Exception('Bản ghi không tồn tại hoặc đã bị xoá');
            }

            // Cập nhật `deleted_at` để xoá mềm bản ghi trong `class_students`
            DB::table('class_students')
                ->where('student_id', $id)
                ->where('class_id', $studentClass->class_id)
                ->update(['deleted_at' => null]);

            return $studentClass;
        });
    }
    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            // Lấy bản ghi học sinh trong bảng class_students đã bị xoá mềm (có trường deleted_at)
            $student = DB::table('class_students')
                ->whereNotNull('deleted_at')
                ->where('student_id', $id)
                ->first();

            if ($student === null) {
                throw new Exception('Không tìm thấy học sinh');
            }

            // Xoá vĩnh viễn học sinh khỏi bảng class_students
            DB::table('class_students')
                ->where('student_id', $id)
                ->whereNotNull('deleted_at')  // Chỉ xoá bản ghi đã bị xoá mềm
                ->delete();

            return response()->json(['message' => 'Học sinh đã được xoá vĩnh viễn khỏi lớp']);
        });
    }
    public function distributeStudents()
{
    return DB::transaction(function () {
        // Lấy danh sách lớp
        $classes = Classes::all();
        if ($classes->isEmpty()) {
            throw new Exception('Không có lớp nào để phân chia học sinh.');
        }

        // Lấy danh sách học sinh chưa được gắn lớp
        $students = User::whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        })->whereDoesntHave('classes')->get();

        if ($students->isEmpty()) {
            throw new Exception('Không có học sinh nào chưa được gắn lớp.');
        }

        // Số lượng học sinh và số lớp
        $totalStudents = $students->count();
        $totalClasses = $classes->count();

        // Số lượng học sinh tối đa mỗi lớp
        $maxStudentsPerClass = 50;

        // Kiểm tra nếu không đủ lớp để chứa học sinh
        if ($totalStudents > $totalClasses * $maxStudentsPerClass) {
            throw new Exception('Không đủ lớp để phân chia học sinh.');
        }

        $currentStudentIndex = 0; // Chỉ số học sinh hiện tại
        $classesWithStudents = 0; // Số lớp đã phân bổ học sinh

        foreach ($classes as $class) {
            // Kiểm tra số lượng học sinh hiện tại trong lớp
            $currentClassStudentCount = $class->students()->count();

            if ($currentClassStudentCount >= $maxStudentsPerClass) {
                continue; // Bỏ qua lớp đã đầy
            }

            // Số lượng học sinh có thể thêm vào lớp
            $availableSlots = $maxStudentsPerClass - $currentClassStudentCount;

            // Lấy danh sách học sinh cần thêm vào lớp
            $classStudents = $students->slice($currentStudentIndex, $availableSlots);

            if ($classStudents->isEmpty()) {
                break;
            }

            // Gắn học sinh vào lớp
            $studentIds = $classStudents->pluck('id')->toArray();
            $class->students()->syncWithoutDetaching(array_fill_keys($studentIds, ['created_at' => Carbon::now()]));

            // Cập nhật chỉ số học sinh hiện tại
            $currentStudentIndex += $availableSlots;
            $classesWithStudents++; // Tăng số lớp đã được gắn học sinh
        }

        // Số học sinh còn dư
        $remainingStudents = $students->slice($currentStudentIndex)->count();

        return [
            'total_students' => $totalStudents,
            'classes_with_students' => $classesWithStudents,
            'remaining_students' => $remainingStudents,
        ];
    });
}

}
