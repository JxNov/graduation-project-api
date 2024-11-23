<?php

namespace App\Services;

use App\Imports\StudentClassImport;
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


    public function distributeStudents()
{
    return DB::transaction(function () {
        $maxStudentsPerClass = 40;
        $maxClasses = 5;

        // Kiểm tra số lượng lớp hiện tại và học sinh trong lớp
        $existingClasses = Classes::withCount('students')->get();
        $fullClasses = $existingClasses->filter(function ($class) use ($maxStudentsPerClass) {
            return $class->students_count >= $maxStudentsPerClass;
        });

        if ($existingClasses->count() >= $maxClasses && $fullClasses->count() == $maxClasses) {
            throw new Exception('Không thể phân lớp. Đã đủ 5 lớp 6 với 40 học sinh mỗi lớp.');
        }

        // Lấy danh sách học sinh chưa được gắn lớp
        $students = User::whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        })->whereDoesntHave('classes')->get();

        $totalStudents = $students->count();

        if ($totalStudents < 30) {
            throw new Exception('Không đủ học sinh để phân lớp.');
        }

        $minStudentsPerClass = 30;

        $studentsForClass = [];
        $remainingStudents = $totalStudents;
        $classesCreated = 0;

        while ($remainingStudents >= $minStudentsPerClass && $classesCreated + $existingClasses->count() < $maxClasses) {
            $studentsForClass[] = $minStudentsPerClass;
            $remainingStudents -= $minStudentsPerClass;
            $classesCreated++;
        }

        $remainingStudentsForClass = $remainingStudents;

        foreach ($studentsForClass as $key => $classStudents) {
            if ($classStudents < $maxStudentsPerClass) {
                $canAddStudents = $maxStudentsPerClass - $classStudents;
                $studentsToAdd = min($remainingStudentsForClass, $canAddStudents);
                $studentsForClass[$key] += $studentsToAdd;
                $remainingStudentsForClass -= $studentsToAdd;

                if ($studentsForClass[$key] >= $maxStudentsPerClass) {
                    continue;
                }
            }

            if ($remainingStudentsForClass <= 0) {
                break;
            }
        }

        if ($remainingStudentsForClass > 0 && $classesCreated + $existingClasses->count() < $maxClasses) {
            $studentsForClass[] = $remainingStudentsForClass;
            $classesCreated++;
        }

        $teachers = User::whereHas('roles', function ($query) {
            $query->where('slug', 'teacher');
        })->get();

        if ($teachers->isEmpty()) {
            throw new Exception('Không có giáo viên nào có role teacher.');
        }

        if ($teachers->count() < $classesCreated) {
            throw new Exception('Không đủ giáo viên để gán cho tất cả các lớp.');
        }

        $teachers = $teachers->shuffle();

        $blockSlug = 'khoi-6'; 
        $block = Block::where('slug', $blockSlug)->firstOrFail();
        $blockId = $block->id;

        for ($i = 1; $i <= $classesCreated; $i++) {
            $teacher = $teachers[$i - 1];

            $class = Classes::create([
                'name' => "6A" . ($existingClasses->count() + $i),
                'slug' => Str::slug("6A" . ($existingClasses->count() + $i)),
                'code' => Str::random(7),
                'teacher_id' => $teacher->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('block_classes')->insert([
                'block_id' => $blockId,
                'class_id' => $class->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $currentStudentIndex = 0;
        foreach ($studentsForClass as $index => $studentsCount) {
            $class = Classes::skip($index)->first();

            $classStudents = $students->slice($currentStudentIndex, $studentsCount);
            $studentIds = $classStudents->pluck('id')->toArray();

            $class->students()->syncWithoutDetaching(array_fill_keys($studentIds, ['created_at' => Carbon::now()]));

            $currentStudentIndex += $studentsCount;
        }

        return [
            'total_students' => $totalStudents,
            'classes_created' => Classes::count(),
            'students_per_class' => Classes::withCount('students')->pluck('students_count', 'name'),
            'remaining_students' => $remainingStudentsForClass,
        ];
    });
}



}
