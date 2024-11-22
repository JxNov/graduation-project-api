<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Subject;
use App\Models\Classes;
use App\Models\Semester;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class StatisticService
{
    public function getStatisticByClassSubjectSemester($subject_slug, $class_slug, $semester_slug)
    {
        // Lấy thông tin subject, class và semester
        $subject = Subject::where('slug', $subject_slug)->firstOrFail();
        $class = Classes::where('slug', $class_slug)->firstOrFail();
        $semester = Semester::where('slug', $semester_slug)->firstOrFail();

        if (!$subject || !$class || !$semester) {
            throw new Exception('Không tìm thấy thông tin môn học, lớp học hoặc học kỳ');
        }

        // Lấy các điểm thi từ bảng Score cho lớp, môn và học kỳ cụ thể
        $scores = Score::where('subject_id', $subject->id)
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->get();

        // Logic tính toán thống kê
        $total_less_than_3_5 = $scores->where('average_score', '<', 3.5)->count();
        $total_between_3_5_5 = $scores->whereBetween('average_score', [3.5, 5])->count();
        $total_between_5_6_5 = $scores->whereBetween('average_score', [5, 6.5])->count();
        $total_between_6_5_8 = $scores->whereBetween('average_score', [6.5, 8])->count();
        $total_between_8_9 = $scores->whereBetween('average_score', [8, 9])->count();
        $total_above_9 = $scores->where('average_score', '>', 9)->count();

        // Tính average_score (TBC)
        $average_score = $scores->avg('average_score');

        // Trả về dữ liệu dưới dạng đối tượng stdClass
        return (object) [
            'class_name' => $class->name,
            'subject_name' => $subject->name,
            'semester_slug' => $semester->name,
            'total_less_than_3_5' => $total_less_than_3_5,
            'total_between_3_5_5' => $total_between_3_5_5,
            'total_between_5_6_5' => $total_between_5_6_5,
            'total_between_6_5_8' => $total_between_6_5_8,
            'total_between_8_9' => $total_between_8_9,
            'total_above_9' => $total_above_9,
            'average_score' => $average_score,
        ];
    }

    // điểm của 1 lớp theo kỳ
    public function getStatisticByClassSemester($class_slug, $semester_slug)
    {
        try {
            $semester = Semester::where('slug', $semester_slug)->first();
            $class = Classes::where('slug', $class_slug)->first();

            if (!$semester || !$class) {
                throw new Exception('Kỳ học, lớp học không tồn tại hoặc đã bị xóa');
            }

            $scores = Score::where('semester_id', $semester->id)
                ->where('class_id', $class->id)
                ->get();

            return $this->calculateScoreStatisticsByClassSemester($scores, $semester->name, $class->name);

        } catch (Exception $e) {
            throw $e;
        }
    }

    // điểm của tất cả lớp theo kỳ
    public function getStatisticAllClassInSemester($semester_slug)
    {
        try {
            $semester = Semester::where('slug', $semester_slug)->first();

            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại hoặc đã bị xóa');
            }

            $academicYear = $semester->academicYear;
            $classes = $academicYear->classes;
            // \Illuminate\Support\Facades\Log::info($classes->toArray());

            $statistic = $classes->map(function ($class) use ($semester) {
                $scores = Score::where('semester_id', $semester->id)
                    ->where('class_id', $class->id)
                    ->get();

                return $this->calculateScoreStatisticsByClassSemester($scores, $semester->name, $class->name);
            });

            return $statistic;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function countStudentInBlockByAcademicYear($academic_year_slug)
    {
        try {
            $academicYear = AcademicYear::where('slug', $academic_year_slug)->first();
            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $classInAcademicYear = $academicYear->classes;
            // \Illuminate\Support\Facades\Log::info($classInAcademicYear->toArray());

            $blockStudentCount = $classInAcademicYear->flatMap(function ($class) use ($academicYear) {
                $studentCount = $class->students->count();

                return $class->blocks->map(function ($block) use ($studentCount, $academicYear) {
                    return [
                        'academicYearName' => $academicYear->name,
                        'blockName' => $block->name,
                        'quantity' => $studentCount,
                    ];
                });
            });

            return $blockStudentCount;

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function countStudentsInBlock($block_slug)
    {
        return DB::transaction(function () use ($block_slug) {
            // Lấy thông tin khối
            $block = Block::where('slug', $block_slug)->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            // Tính tổng số học sinh trong các lớp thuộc khối
            $studentsCount = User::whereHas('classes', function ($query) use ($block) {
                $query->whereHas('blocks', function ($subQuery) use ($block) {
                    $subQuery->where('block_id', $block->id); // Kiểm tra khối từ bảng block_classes
                });
            })->count();

            return [
                'total_students' => $studentsCount
            ];
        });
    }

    public function getGenderRatioInBlock($block_slug)
    {
        return DB::transaction(function () use ($block_slug) {
            // Lấy thông tin khối từ slug
            $block = Block::where('slug', $block_slug)->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            // Lấy danh sách học sinh thuộc các lớp của khối
            $students = User::whereHas('classes', function ($query) use ($block) {
                $query->whereHas('blocks', function ($subQuery) use ($block) {
                    $subQuery->where('block_id', $block->id);
                });
            })->get();
            // Đếm số lượng học sinh nam và nữ
            $genderCount = $students->groupBy('gender')->map(function ($group) {
                return $group->count();
            });
            $maleCount = $genderCount->get('Male', 0);
            $femaleCount = $genderCount->get('Female', 0);
            $total = $maleCount + $femaleCount;

            if ($total === 0) {
                throw new Exception('Không có học sinh nào trong khối này.');
            }

            // Tính tỷ lệ phần trăm cho nam và nữ
            $maleRatio = round(($maleCount / $total) * 100, 2);
            $femaleRatio = round(($femaleCount / $total) * 100, 2);


            return [
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'male_ratio' => $maleRatio . '%',
                'female_ratio' => $femaleRatio . '%'
            ];
        });
    }

    // hàm tính toán thống kê của lớp theo kỳ
    private function calculateScoreStatisticsByClassSemester($scores, $semesterName, $className)
    {
        $total_students = $scores->count();

        $total_less_than_3_5 = $scores->where('average_score', '<', 3.5)->count();
        $total_between_3_5_5 = $scores->whereBetween('average_score', [3.5, 5])->count();
        $total_between_5_6_5 = $scores->whereBetween('average_score', [5, 6.5])->count();
        $total_between_6_5_8 = $scores->whereBetween('average_score', [6.5, 8])->count();
        $total_between_8_9 = $scores->whereBetween('average_score', [8, 9])->count();
        $total_above_9 = $scores->where('average_score', '>', 9)->count();

        $students_passed = $scores->where('average_score', '>=', 5)->count();
        $students_failed = $scores->where('average_score', '<', 5)->count();

        $highest_score = $scores->max('average_score');
        $lowest_score = $scores->min('average_score');

        $pass_rate = ($total_students > 0) ? ($students_passed / $total_students) * 100 : 0;
        $fail_rate = ($total_students > 0) ? ($students_failed / $total_students) * 100 : 0;

        $average_score = number_format($scores->avg('average_score'), 2);

        return [
            'semesterName' => $semesterName,
            'className' => $className,
            'total_less_than_3_5' => $total_less_than_3_5,
            'total_between_3_5_5' => $total_between_3_5_5,
            'total_between_5_6_5' => $total_between_5_6_5,
            'total_between_6_5_8' => $total_between_6_5_8,
            'total_between_8_9' => $total_between_8_9,
            'total_above_9' => $total_above_9,
            'averageScoreOfSemester' => $average_score,
            'studentPassSemester' => $students_passed,
            'studentFailedSemester' => $students_failed,
            'studentHightestScore' => $highest_score,
            'studentLowestScore' => $lowest_score,
            'passRate' => $pass_rate,
            'failRate' => $fail_rate,
        ];
    }
}
