<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\ClassPeriod;

class ScheduleService
{
    public function generateTimetable()
    {
        // Cập nhật thời khóa biểu ở đây nếu cần thiết
    }

    public function createTimetable($semesterSlug, $academicYearSlug, $subjects, $teachers, $dailyPeriods): array
    {
        // Tìm Semester và AcademicYear dựa trên slug
        $semester = Semester::where('slug', $semesterSlug)->first();
        $academicYear = AcademicYear::where('slug', $academicYearSlug)->first();

        if (!$semester || !$academicYear) {
            // Nếu không tìm thấy Semester hoặc AcademicYear, trả về lỗi hoặc thông báo
            return ['error' => 'Semester or Academic Year not found.'];
        }

        // Lấy danh sách các lớp học từ AcademicYear và Semester
        $classes = $academicYear->classes()
            ->whereHas('semesters', function ($query) use ($semester) {
                $query->where('semester_id', $semester->id);
            })
            ->get();

        // Kiểm tra nếu không có lớp nào được tìm thấy
        if ($classes->isEmpty()) {
            return ['error' => 'No classes found for the selected semester and academic year.'];
        }

        $schedule = [];

        // Tạo khung thời khóa biểu cho từng lớp
        foreach ($classes as $cls) {
            $schedule[$cls['id']] = [];

            // Lặp qua các ngày trong tuần và các tiết học buổi sáng, chiều
            foreach ($dailyPeriods as $day => $sessions) {
                $schedule[$cls['id']][$day] = [
                    'morning' => [],
                    'afternoon' => []
                ];

                // Xử lý các tiết học buổi sáng
                foreach ($sessions['morning'] as $session) {
                    if (in_array($cls['id'], $session['class_ids'])) {
                        $periodCount = $session['periods'];
                        $schedule[$cls['id']][$day]['morning'] = array_fill(0, $periodCount, null);
                    }
                }

                // Xử lý các tiết học buổi chiều
                foreach ($sessions['afternoon'] as $session) {
                    if (in_array($cls['id'], $session['class_ids'])) {
                        $periodCount = $session['periods'];
                        $schedule[$cls['id']][$day]['afternoon'] = array_fill(0, $periodCount, null);
                    }
                }
            }
        }

        // Đặt tiết "Chào cờ" và "Sinh hoạt lớp"
        foreach ($classes as $cls) {
            // "Chào cờ" vào sáng thứ Hai
            $schedule[$cls['id']]['Monday']['morning'][0] = [
                'subject_id' => 1,
                'subject_name' => 'Chào cờ',
            ];

            // "Sinh hoạt lớp" vào sáng thứ Bảy
            $saturdayMorningPeriods = count($schedule[$cls['id']]['Saturday']['morning']);
            if ($saturdayMorningPeriods > 0) {
                $schedule[$cls['id']]['Saturday']['morning'][$saturdayMorningPeriods - 1] = [
                    'subject_id' => 2,
                    'subject_name' => 'Sinh hoạt lớp',
                    'teacher_id' => $cls['teacher_id'],
                    'teacher_name' => $teachers[$cls['teacher_id'] - 1]['name'],
                ];
            }
        }

        // Đảm bảo các lớp 6, 7 học vào buổi sáng và các lớp 8, 9 học vào buổi chiều
        foreach ($classes as $cls) {
            if (in_array($cls['block_id'], [6, 7])) {
                // Đảm bảo lớp 6,7 học buổi sáng
                foreach ($dailyPeriods as $day => &$sessions) {
                    $sessions['afternoon'] = []; // Xóa buổi chiều nếu là khối 6,7
                }
            } elseif (in_array($cls['block_id'], [8, 9])) {
                // Đảm bảo lớp 8,9 học buổi chiều
                foreach ($dailyPeriods as $day => &$sessions) {
                    $sessions['morning'] = []; // Xóa buổi sáng nếu là khối 8,9
                }
            }
        }

        // Sắp xếp môn học với backtracking
        $success = $this->assignSubjectsAndTeachersWithBacktracking($schedule, $classes, $subjects, $teachers, $dailyPeriods);

        if (!$success) {
            dd("Không thể tạo thời khóa biểu");
        }

        return $schedule;
    }


    public function assignSubjectsAndTeachersWithBacktracking(&$schedule, $classes, $subjects, $teachers, $dailyPeriods): bool
    {
        $classSubjectPeriods = [];
        foreach ($classes as $cls) {
            foreach ($subjects as $subject) {
                $classSubjectPeriods[$cls['id']][$subject['id']] = 0;
            }
        }
        return $this->backtrack($schedule, $classes, $subjects, $teachers, $dailyPeriods, $classSubjectPeriods);
    }

    public function backtrack(&$schedule, $classes, $subjects, $teachers, $dailyPeriods, &$classSubjectPeriods, $currentClass = 0): bool
    {
        if ($currentClass >= count($classes)) {
            return true; // Thành công nếu tất cả lớp đã hoàn thành phân bổ
        }

        $cls = $classes[$currentClass];
        foreach ($dailyPeriods as $day => $sessions) {
            foreach (['morning', 'afternoon'] as $session) {
                foreach ($schedule[$cls['id']][$day][$session] as $periodIndex => &$period) {
                    if ($period !== null) continue; // Bỏ qua tiết đã có môn

                    // Chọn môn học ngẫu nhiên từ danh sách môn còn tiết trống
                    $subject = $this->getRandomSubject($subjects, $cls['id'], $classSubjectPeriods, $schedule[$cls['id']][$day][$session]);
                    if (!$subject) continue; // Bỏ qua nếu không tìm thấy môn học

                    // Tìm giáo viên cho môn học được chọn
                    $teacher = $this->getAvailableTeacher($teachers, $subject['id'], $cls['id']);
                    if (!$teacher) continue; // Bỏ qua nếu không tìm thấy giáo viên

                    // Gán tiết học với môn và giáo viên
                    $period = [
                        'subject_id' => $subject['id'],
                        'subject_name' => $subject['name'],
                        'teacher_id' => $teacher['id'],
                        'teacher_name' => $teacher['name']
                    ];
                    $classSubjectPeriods[$cls['id']][$subject['id']]++;

                    // Gọi đệ quy cho lớp hiện tại (để hoàn thành tất cả các tiết trước khi chuyển sang lớp tiếp theo)
                    if ($this->backtrack($schedule, $classes, $subjects, $teachers, $dailyPeriods, $classSubjectPeriods, $currentClass)) {
                        return true; // Thành công nếu lấp đầy được tất cả các tiết
                    }

                    // Nếu không thành công, hoàn tác gán và thử lại
                    $period = null;
                    $classSubjectPeriods[$cls['id']][$subject['id']]--;
                }
            }
        }

        // Khi không phân bổ được, thử lại với lớp tiếp theo
        return $this->backtrack($schedule, $classes, $subjects, $teachers, $dailyPeriods, $classSubjectPeriods, $currentClass + 1);
    }

    public function getRandomSubject($subjects, $classId, $classSubjectPeriods, $currentSession)
    {
        $availableSubjects = [];

        foreach ($subjects as $subject) {
            $currentCount = $classSubjectPeriods[$classId][$subject['id']] ?? 0;

            if ($subject['id'] === 1 || $subject['id'] === 2) continue; // Skip "Chào cờ" and "Sinh hoạt lớp"

            // Nếu là môn học chỉ dành cho một khối cụ thể thì bỏ qua nếu không phải khối đó
            if (isset($subject['block_id']) && !in_array($classId, $subject['block_id'])) continue;

            if ($currentCount >= $subject['max_periods']) continue;

            // Kiểm tra xem môn học đã được phân bổ trong buổi học hiện tại chưa
            $found = false;
            foreach ($currentSession as $period) {
                if ($period !== null && $period['subject_id'] === $subject['id']) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $availableSubjects[] = $subject;
            }
        }

        // Chọn ngẫu nhiên một môn học từ danh sách môn học còn trống
        if (!empty($availableSubjects)) {
            return $availableSubjects[array_rand($availableSubjects)];
        }

        return null; // Trả về null nếu không tìm thấy môn học phù hợp
    }

    public function getAvailableTeacher($teachers, $subjectId, $classId)
    {
        $availableTeachers = [];
        foreach ($teachers as $teacher) {
            if (in_array($subjectId, $teacher['subject_ids']) && in_array($classId, $teacher['block_ids'])) {
                $availableTeachers[] = $teacher;
            }
        }

        if (empty($availableTeachers)) {
            return null;
        }

        return $availableTeachers[array_rand($availableTeachers)];
    }
}
