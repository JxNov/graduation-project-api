<?php

namespace App\Services;

use App\Imports\StudentClassImport;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

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


    public function distributeStudents($academic_year_slug, $blockSlug)
{
    return DB::transaction(function () use ($academic_year_slug, $blockSlug) {
        // Lấy năm học theo slug
        $academicYear = AcademicYear::where('slug', $academic_year_slug)->first();

        if (!$academicYear) {
            throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
        }

        // Lấy khối học theo slug
        $block = Block::where('slug', $blockSlug)->first();
        if (!$block) {
            throw new Exception('Khối học không tồn tại hoặc đã bị xóa');
        }

        $blockId = $block->id;

        // Lấy danh sách học sinh chưa được gắn lớp và đã nhập học vào năm học này
        $students = User::whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        })
        ->whereDoesntHave('classes')
        ->whereHas('generations', function ($query) use ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        })
        ->get();

        $totalStudents = $students->count();

        if ($totalStudents < 1) {
            throw new Exception('Không có học sinh để phân lớp.');
        }

        // Tính toán số lượng lớp cần thiết
        $maxStudentsPerClass = 40;
        $classesNeeded = ceil($totalStudents / $maxStudentsPerClass);

        // Tạo danh sách giáo viên
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('slug', 'teacher');
        })->get();

        if ($teachers->isEmpty()) {
            throw new Exception('Không có giáo viên nào có role teacher.');
        }

        $teachers = $teachers->shuffle();

        if ($teachers->count() < $classesNeeded) {
            throw new Exception('Không đủ giáo viên để gán cho tất cả các lớp.');
        }

        // Số lượng học sinh cho mỗi lớp
        $studentsPerClass = (int) floor($totalStudents / $classesNeeded);
        $remainingStudents = $totalStudents % $classesNeeded;

        // Tạo lớp và phân phối học sinh
        $currentStudentIndex = 0;
        $classesCreated = [];

        for ($i = 1; $i <= $classesNeeded; $i++) {
            $teacher = $teachers[$i - 1];

            // Tạo lớp
            $class = Classes::create([
                'name' => "6A" . ($i),
                'slug' => Str::slug($teacher->username."-"."6A" . ($i)),
                'code' => Str::random(7),
                'teacher_id' => $teacher->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Ghi vào bảng block_classes
            DB::table('block_classes')->insert([
                'block_id' => $blockId,
                'class_id' => $class->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Ghi vào bảng academic_year_classes
            DB::table('academic_year_classes')->insert([
                'academic_year_id' => $academicYear->id,
                'class_id' => $class->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Ghi vào bảng class_teachers
            DB::table('class_teachers')->insert([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Tính số lượng học sinh cho lớp này
            $studentsForThisClass = $studentsPerClass;
            if ($remainingStudents > 0) {
                $studentsForThisClass++;
                $remainingStudents--;
            }

            // Gán học sinh vào lớp
            $studentsForClass = $students->slice($currentStudentIndex, $studentsForThisClass);
            $studentIds = $studentsForClass->pluck('id')->toArray();

            $class->students()->syncWithoutDetaching(array_fill_keys($studentIds, ['created_at' => Carbon::now()]));

            $currentStudentIndex += $studentsForThisClass;
            $classesCreated[] = $class;
        }

        return [
            'total_students' => $totalStudents,
            'classes_created' => count($classesCreated),
            'students_per_class' => Classes::withCount('students')->pluck('students_count', 'name'),
        ];
    });
}







}
