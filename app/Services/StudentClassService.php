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
        $academicYear = AcademicYear::where('slug', $academic_year_slug)->firstOrFail();
        $block = Block::where('slug', $blockSlug)->firstOrFail();

        // Lấy danh sách học sinh chưa được phân lớp
        $students = User::whereHas('roles', fn($query) => $query->where('slug', 'student'))
            ->whereDoesntHave('classes')
            ->whereHas('generations', fn($query) => $query->where('academic_year_id', $academicYear->id))
            ->get();

        if ($students->isEmpty()) {
            return ['message' => 'Không có học sinh mới để phân lớp.'];
        }

        // Lấy danh sách các lớp hiện tại trong khối
        $existingClasses = Classes::whereHas('blocks', fn($query) => $query->where('block_id', $block->id))
            ->withCount('students')
            ->get();

        $remainingStudents = $students->count();
        $unassignedStudents = collect();
        $maxStudentsPerClass = 45;
        $minStudentsForNewClass = 25;

        // Phân học sinh vào các lớp hiện tại
        foreach ($existingClasses as $class) {
            $availableSlots = $maxStudentsPerClass - $class->students_count;

            if ($availableSlots > 0 && $remainingStudents > 0) {
                $studentsToAssign = $students->splice(0, min($availableSlots, $remainingStudents));
                $class->students()->syncWithoutDetaching(
                    array_fill_keys($studentsToAssign->pluck('id')->toArray(), ['created_at' => now()])
                );
                $remainingStudents -= $studentsToAssign->count();
            }
        }

        // Nếu vẫn còn học sinh, tạo thêm lớp mới
        $teachers = User::whereHas('roles', fn($query) => $query->where('slug', 'teacher'))->get()->shuffle();
        $newClasses = [];

        while ($remainingStudents >= $minStudentsForNewClass) {
            $teacher = $teachers->pop();
            if (!$teacher) {
                throw new Exception('Không còn giáo viên để tạo thêm lớp.');
            }

            $class = Classes::create([
                'name' => "6A" . (count($existingClasses) + count($newClasses) + 1),
                'slug' => Str::slug($teacher->username . '-6A' . (count($existingClasses) + count($newClasses) + 1)),
                'code' => Str::random(7),
                'teacher_id' => $teacher->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ghi vào các bảng trung gian
            DB::table('block_classes')->insert([
                'block_id' => $block->id,
                'class_id' => $class->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('academic_year_classes')->insert([
                'academic_year_id' => $academicYear->id,
                'class_id' => $class->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('class_teachers')->insert([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gán học sinh vào lớp mới
            $studentsToAssign = $students->splice(0, $maxStudentsPerClass);
            $class->students()->syncWithoutDetaching(
                array_fill_keys($studentsToAssign->pluck('id')->toArray(), ['created_at' => now()])
            );
            $remainingStudents -= $studentsToAssign->count();
            $newClasses[] = $class;
        }

        // Học sinh không đủ để phân lớp
        if ($remainingStudents > 0) {
            $unassignedStudents = $students;
        }

        return [
            'total_students' => $students->count(),
            'new_classes_created' => count($newClasses),
            'students_per_class' => $existingClasses->merge($newClasses)->mapWithKeys(fn($class) => [$class->name => $class->students()->count()]),
            'unassigned_students' => $unassignedStudents->pluck('name'),
        ];
    });
}








}
