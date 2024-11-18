<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Subject;
use App\Models\Classes;
use App\Models\Semester;
use App\Models\AcademicYear;
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
        return (object)[
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
}
