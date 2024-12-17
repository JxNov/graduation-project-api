<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use App\Models\Subject;
use App\Models\Block;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class ScheduleService
{
    public function generateSchedules($blockSlug)
    {
        // Lấy block theo slug
        $block = Block::where('slug', $blockSlug)->first();

        $classes = $block->classes()->whereNull('classes.deleted_at')->get();

        // Lấy tất cả môn học trong block và thuộc năm học
        $filteredSubjects = Subject::whereHas('blocks', function ($query) use ($block) {
            $query->where('block_id', $block->id);
        })
            ->get();

        // Số tiết mỗi môn học trong tuần
        $subjectHoursPerWeek = [
            'toan' => 4,
            'ngu-van' => 4,
            'tieng-anh' => 4,
            'hoa-hoc' => 2, // chỉ cho khối 8 và 9
            'vat-ly' => 2,
            'lich-su' => 2,
            'sinh-hoc' => 1,
            'gdcd' => 1,
            'dia-ly' => 1,
            'cong-nghe' => 1,
            'tin-hoc' => 1,
            'the-duc' => 1,
            'am-nhac' => 1,
            'my-thuat' => 1,
        ];

        // Lọc các môn học trong block và năm học
        $subjectsInBlock = $filteredSubjects->filter(function ($subject) use ($subjectHoursPerWeek) {
            return array_key_exists($subject->slug, $subjectHoursPerWeek);
        });

        // Lịch học cho mỗi lớp
        $schedule = [];
        $classPeriods = DB::table('class_periods')->get(); // Lấy tất cả các tiết học

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($classes as $class) {
            $classSchedule = [];
            $dailyPeriods = []; // Đếm số tiết mỗi ngày

            // Khởi tạo số tiết mỗi ngày (mỗi ngày tối đa 4 tiết)
            foreach ($days as $day) {
                $dailyPeriods[$day] = 0;
            }

            // Phân chia số tiết các môn học
            foreach ($subjectsInBlock as $subject) {
                $periodsPerWeek = $subjectHoursPerWeek[$subject->slug];
                $remainingPeriods = $periodsPerWeek;

                // Lấy danh sách giáo viên dạy môn học đó
                $teachers = DB::table('subject_teachers')
                    ->where('subject_id', $subject->id)
                    ->pluck('teacher_id');

                // Phân bổ số tiết vào các ngày trong tuần
                while ($remainingPeriods > 0) {
                    // Sử dụng hàm shuffle để xáo trộn các ngày trong tuần
                    $shuffledDays = $days;
                    shuffle($shuffledDays);

                    foreach ($shuffledDays as $day) {
                        if ($remainingPeriods <= 0) {
                            break;
                        }

                        // Mỗi ngày có tối đa 4 tiết học
                        if ($dailyPeriods[$day] < 4) {
                            $availablePeriods = $classPeriods->whereNotIn(
                                'id',
                                collect($classSchedule)->where('days', $day)->pluck('class_period_id')
                            )->pluck('id')->toArray();

                            if (!empty($availablePeriods)) {
                                $periodId = $availablePeriods[array_rand($availablePeriods)];
                                $teacherId = $teachers->random();

                                $classSchedule[] = [
                                    'class_id' => $class->id,
                                    'subject_id' => $subject->id,
                                    'teacher_id' => $teacherId,
                                    'class_period_id' => $periodId,
                                    'days' => $day,
                                ];

                                $dailyPeriods[$day]++;
                                $remainingPeriods--;
                            }
                        }
                    }
                }
            }

            // Kiểm tra nếu đã đủ số tiết học (28 tiết trong tuần)
            $totalPeriods = count($classSchedule);
            if ($totalPeriods < 28) {
                // Thêm các môn còn lại nếu cần
                $remainingSubjects = $subjectsInBlock->filter(function ($subject) use ($subjectHoursPerWeek) {
                    return !array_key_exists($subject->slug, $subjectHoursPerWeek);
                });

                foreach ($remainingSubjects as $subject) {
                    $remainingPeriods = 28 - $totalPeriods;
                    while ($remainingPeriods > 0) {
                        // Sử dụng hàm shuffle để xáo trộn các ngày trong tuần
                        $shuffledDays = $days;
                        shuffle($shuffledDays);

                        foreach ($shuffledDays as $day) {
                            if ($remainingPeriods <= 0) {
                                break;
                            }

                            if ($dailyPeriods[$day] < 4) {
                                $availablePeriods = $classPeriods->whereNotIn(
                                    'id',
                                    collect($classSchedule)->where('days', $day)->pluck('class_period_id')
                                )->pluck('id')->toArray();

                                if (!empty($availablePeriods)) {
                                    $periodId = $availablePeriods[array_rand($availablePeriods)];
                                    $teacherId = $teachers->random();

                                    $classSchedule[] = [
                                        'class_id' => $class->id,
                                        'subject_id' => $subject->id,
                                        'teacher_id' => $teacherId,
                                        'class_period_id' => $periodId,
                                        'days' => $day,
                                    ];

                                    $dailyPeriods[$day]++;
                                    $remainingPeriods--;
                                }
                            }
                        }
                    }
                }
            }

            // Lưu lịch học vào mảng
            $schedule[$class->id] = $classSchedule;
        }

        // Lưu lịch vào cơ sở dữ liệu
        foreach ($schedule as $classId => $classSchedule) {
            foreach ($classSchedule as $entry) {
                DB::table('schedules')->insert($entry);
            }
        }

        return $schedule;
    }
    public function updateScheduleClass($data, $classSlug)
{
    return DB::transaction(function () use ($data, $classSlug) {
        
        $class = Classes::where('slug', $classSlug)->first();
        if ($class === null) {
            throw new Exception('Class không tồn tại hoặc đã bị xóa');
        }

        
        $subject = Subject::where('slug', $data['subjectSlug'])->first();
        if ($subject === null) {
            throw new Exception('Subject không tồn tại hoặc đã bị xóa');
        }

        $teacher = User::where('username', $data['usernameTeacher'])->first();
        if (!$teacher) {
            throw new Exception('Không tìm thấy giáo viên');
        }

        $isTeacherAssignedToSubject = $subject->teachers()->where('teacher_id', $teacher->id)->exists();
        if (!$isTeacherAssignedToSubject) {
            throw new Exception('Giáo viên không dạy môn học này');
        }

        // Kiểm tra days
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        if (!isset($data['days']) || !in_array($data['days'], $validDays)) {
            throw new Exception('Day không hợp lệ. Phải là một trong các ngày: ' . implode(', ', $validDays));
        }

        // Kiểm tra is_morning
        if (!isset($data['is_morning']) || !in_array($data['is_morning'], [0, 1])) {
            throw new Exception('is_morning không hợp lệ (chỉ được là 0 hoặc 1)');
        }

        // kiểm tra xem gv đã dạy lớp khác buổi chiều chưa
        if ($data['is_morning'] == 0) {
            $existingSchedule = Schedule::where('teacher_id', $teacher->id)
                ->where('subject_id', $subject->id)
                ->where('days', $data['days'])
                ->where('is_morning', 0)
                ->exists();

            if ($existingSchedule) {
                throw new Exception('Giáo viên đã dạy môn này vào buổi chiều ở lớp khác vào ngày ' . $data['days']);
            }
        }


        $schedule = Schedule::updateOrCreate(
            [
                'class_id' => $class->id,
                'days' => $data['days'],
                'is_morning' => $data['is_morning'],
            ],
            [
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id, 
            ]
        );


        return (object)[
            'className' => $class->name, 
            'subjectName' => $subject->name, 
            'teacherName' => $teacher->name, 
            'usernameTeacher'=>$teacher->username,
            'days' => $schedule->days, 
            'is_morning' => $schedule->is_morning,
        ];
    });
}






}
