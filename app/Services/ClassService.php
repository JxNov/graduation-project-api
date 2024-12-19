<?php
namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassService
{
    public function createNewClass(array $data)
    {
        return DB::transaction(function () use ($data) {
            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            // lấy tất cả giáo viên
            $teacher = User::whereHas('roles', function ($role) {
                $role->where('slug', 'like', 'teacher');
            })
                ->where('username', $data['username'])
                ->first();

            if ($teacher === null) {
                throw new Exception('Người này không phải giáo viên');
            }

            // kiểm tra nếu giáo viên đã có trong 1 lớp cùng năm học
            $homeRoomTeacher = Classes::where('teacher_id', $teacher->id)
                ->whereHas('academicYears', function ($query) use ($academicYear) {
                    $query->where('academic_years.id', $academicYear->id);
                })
                ->first();

            if ($homeRoomTeacher) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $teacherSlug = Str::slug($teacher->username);
            $classSlug = Str::slug($data['name']);
            $data['slug'] = $teacherSlug . '-' . $classSlug;

            $class = Classes::create($data);

            $class->classTeachers()->sync([$teacher->id]);
            $class->academicYears()->sync([$academicYear->id]);
            $class->blocks()->sync([$block->id]);

            $subjectIds = Subject::whereHas('blocks', function ($query) use ($block) {
                $query->where('blocks.id', $block->id);
            })->pluck('id');

            $class->subjects()->sync($subjectIds);

            return $class;
        });
    }

    public function updateClass(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $teacher = User::whereHas('roles', function ($role) {
                $role->where('slug', 'like', 'teacher');
            })
                ->where('username', $data['username'])
                ->first();

            if ($teacher === null) {
                throw new Exception('Người này không phải giáo viên');
            }

            $homeRoomTeacher = Classes::where('teacher_id', $teacher->id)
                ->whereHas('academicYears', function ($query) use ($academicYear) {
                    $query->where('academic_years.id', $academicYear->id);
                })
                ->first();

            if ($homeRoomTeacher && $homeRoomTeacher->id !== $class->id) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $class->update($data);

            $class->classTeachers()->sync([$teacher->id]);
            $class->academicYears()->sync([$academicYear->id]);
            $class->blocks()->sync([$block->id]);

            $subjectIds = Subject::whereHas('blocks', function ($query) use ($block) {
                $query->where('blocks.id', $block->id);
            })->pluck('id');

            $class->subjects()->sync($subjectIds);

            return $class;
        });
    }

    public function assignClassToTeacher(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $userIds = [];
            $users = User::whereIn('username', $data['username'])->get();
            $userIds = $users->pluck('id')->toArray();

            $class->classTeachers()->sync($userIds);

            return $class;
        });
    }

    public function promoteStudent(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $students = $class->students;

            if ($students->isEmpty()) {
                throw new Exception('Không có học sinh nào trong lớp này');
            }

            $requiredSubjects = ['toan', 'ngu-van', 'tieng-anh'];

            $subjects = Subject::whereIn('slug', $requiredSubjects)
                ->pluck('id', 'slug');

            $eligibleStudents = [];
            foreach ($students as $student) {
                $finalScore = DB::table('final_scores')
                    ->where('student_id', $student->id)
                    ->where('semester_id', null)
                    ->where('class_id', $class->id)
                    ->first();

                if (!$finalScore || $finalScore->average_score < 5) {
                    continue;
                }

                $failedSubjects = DB::table('subject_scores')
                    ->whereIn('subject_id', $subjects->values())
                    ->where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('average_score', '<', 5)
                    ->count();

                if ($failedSubjects > 0) {
                    continue;
                }

                $eligibleStudents[] = $student->id;
            }

            // \Illuminate\Support\Facades\Log::info($eligibleStudents);

            if (empty($eligibleStudents)) {
                throw new Exception('Không có học sinh nào trong lớp này đủ điều kiện lên lớp');
            }

            $newClass = $this->createNewClass($data);

            $newClass->students()->sync($eligibleStudents);

            return $newClass;
        });
    }
    public function getFailedStudents()
    {
        return DB::transaction(function () {
            $classes = Classes::all();

            if ($classes->isEmpty()) {
                throw new Exception('Không có lớp nào trong hệ thống');
            }

            // Lấy ID của các môn Toán, Ngữ Văn, Tiếng Anh
            $requiredSubjects = ['toan', 'ngu-van', 'tieng-anh'];
            $subjects = Subject::whereIn('slug', $requiredSubjects)
                ->pluck('id', 'slug');

            // Khởi tạo danh sách học sinh bị đúp và bộ đếm
            $failedStudents = [];
            $totalFailed = 0;

            // Duyệt qua từng lớp
            foreach ($classes as $class) {
                $students = $class->students;

                if ($students->isEmpty()) {
                    continue; // Bỏ qua lớp không có học sinh
                }

                // Kiểm tra từng học sinh
                foreach ($students as $student) {
                    // Lấy điểm tổng kết (nếu có)
                    $finalScore = DB::table('final_scores')
                        ->where('student_id', $student->id)
                        ->where('semester_id', null) // Học kỳ tổng kết
                        ->first();

                    // Nếu điểm tổng < 5 hoặc không có điểm tổng
                    if (!$finalScore || $finalScore->average_score < 5) {
                        $failedStudents[] = [
                            'studentName' => $student->name,
                            'username' => $student->username,
                            'className' => $class->name,
                            'info' => 'Điểm tổng < 5',
                        ];
                        $totalFailed++;
                        continue;
                    }

                    // Kiểm tra điểm 3 môn chính
                    $failedSubjects = DB::table('subject_scores')
                        ->whereIn('subject_id', $subjects->values()) // Chỉ xét 3 môn
                        ->where('student_id', $student->id)
                        ->where('class_id', $class->id)
                        ->where('average_score', '<', 5) // Điểm môn < 5
                        ->count();

                    // Nếu có ít nhất 1 môn chính < 5
                    if ($failedSubjects > 0) {
                        $failedStudents[] = [
                            'studentName' => $student->name,
                            'username' => $student->username,
                            'className' => $class->name,
                            'info' => 'Một hoặc nhiều môn chính < 5',
                        ];
                        $totalFailed++;
                    }
                }
            }

            // Nếu không có học sinh nào bị đúp
            if (empty($failedStudents)) {
                throw new Exception('Không có học sinh nào bị đúp');
            }

            return (object) [
                'failedStudents' => $failedStudents,
                'totalFailed' => $totalFailed,
            ];
        });
    }


    public function deleteClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $class->delete();
        });
    }

    public function restoreClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::onlyTrashed()->where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $academicYearClass = DB::table('academic_year_classes')->where('class_id', $class->id)->first();
            // dd($academicYearClass);

            $academicYear = AcademicYear::withTrashed()->where('id', $academicYearClass->academic_year_id)->first();

            if ($academicYear === null) {
                throw new Exception('Cần khôi phục năm học của lớp học trước');
            }

            if ($academicYear->trashed()) {
                throw new Exception('Cần khôi phục năm học của lớp học trước');
            }

            $class->restore();

            return $class;
        });
    }

    public function forceDeleteClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::withTrashed()->where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $class->forceDelete();
        });
    }
}