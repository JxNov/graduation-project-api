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
        // Lấy danh sách học sinh chưa được gắn lớp
        $students = User::whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        })->whereDoesntHave('classes')->get();

        $totalStudents = $students->count();

        if ($totalStudents < 30) {
            throw new Exception('Không đủ học sinh để phân lớp.');
        }

        // tối thiểu 30, tối đa 40 học sinh
        $minStudentsPerClass = 30;
        $maxStudentsPerClass = 40;
        // tối đa đc 5 lớp
        $maxClasses = 5;

        // tạo 1 mảng chứa học sinh của lớp
        $studentsForClass = [];
        //số dư học sinh = tổng số học sinh
        $remainingStudents = $totalStudents;

        // tạo lớp cho đến khi đủ lớp cho tổng số học sinh hoặc = 5 lớp
        $classesCreated = 0;
        // nếu số dư học sinh >= sô hs tối thiểu và số lớp hiện tại < max lớp
        while ($remainingStudents >= $minStudentsPerClass && $classesCreated < $maxClasses) {
            // tạo một lớp mới với tối thiểu 30 học sinh
            $studentsForClass[] = $minStudentsPerClass;
            // sau đó trừ số học sinh đã được gán lớp ở trên
            $remainingStudents -= $minStudentsPerClass;
            $classesCreated++;
        }

        // số hs còn lại khi đã đủ 5 lớp 30 hs
        $remainingStudentsForClass = $remainingStudents;

        foreach ($studentsForClass as $key => $classStudents) {
            // Nếu lớp chưa đạt tối đa học sinh, phân bổ học sinh dư vào lớp
            if ($classStudents < $maxStudentsPerClass) {
                $canAddStudents = $maxStudentsPerClass - $classStudents;
                $studentsToAdd = min($remainingStudentsForClass, $canAddStudents);
                $studentsForClass[$key] += $studentsToAdd;
                $remainingStudentsForClass -= $studentsToAdd;

                // Nếu lớp đã đủ 40 học sinh, dừng phân bổ cho lớp này
                if ($studentsForClass[$key] >= $maxStudentsPerClass) {
                    continue;
                }
            }

            // Nếu không còn học sinh dư để phân bổ, dừng
            if ($remainingStudentsForClass <= 0) {
                break;
            }
        }

        // Tạo lớp mới nếu còn đủ học sinh để tạo lớp
        if ($remainingStudentsForClass > 0 && $classesCreated < $maxClasses) {
            $studentsForClass[] = $remainingStudentsForClass;
            $classesCreated++;
        }

        // Lấy danh sách các lớp đã tạo
        $existingClassesCount = Classes::count();

        // Lấy danh sách giáo viên có role là 'teacher'
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('slug', 'teacher');
        })->get();

        if ($teachers->isEmpty()) {
            throw new Exception('Không có giáo viên nào có role teacher.');
        }

        // Kiểm tra số lượng giáo viên có đủ không
        if ($teachers->count() < $classesCreated) {
            throw new Exception('Không đủ giáo viên để gán cho tất cả các lớp.');
        }

        // Xáo trộn danh sách giáo viên
        $teachers = $teachers->shuffle();

        // Tạo lớp và gán giáo viên
        for ($i = $existingClassesCount + 1; $i <= $classesCreated; $i++) {
            $teacher = $teachers[$i - 1];

            $class = Classes::create([
                'name' => "6A$i",
                'slug' => Str::slug("6A$i"),
                'code' => Str::random(7),
                'teacher_id' => $teacher->id, // Gán giáo viên cho lớp
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Phân chia học sinh vào các lớp đã tạo
        $currentStudentIndex = 0;
        foreach ($studentsForClass as $index => $studentsCount) {
            // Lấy lớp tương ứng
            $class = Classes::skip($index)->first(); 

            // Lấy danh sách học sinh cho lớp
            $classStudents = $students->slice($currentStudentIndex, $studentsCount);
            $studentIds = $classStudents->pluck('id')->toArray();

            // Gắn học sinh vào lớp
            $class->students()->syncWithoutDetaching(array_fill_keys($studentIds, ['created_at' => Carbon::now()]));

            // Cập nhật chỉ số học sinh hiện tại
            $currentStudentIndex += $studentsCount;
        }

        // Trả về kết quả
        return [
            'total_students' => $totalStudents,
            'classes_created' => Classes::count(),
            'students_per_class' => Classes::withCount('students')->pluck('students_count', 'name'),
            'remaining_students' => $remainingStudentsForClass,
        ];
        
    });
}

}
