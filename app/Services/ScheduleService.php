<?php

namespace App\Services;

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
        $blocks = [
            ['id' => 1, 'name' => 'Khối 6'],
            ['id' => 2, 'name' => 'Khối 7'],
            ['id' => 3, 'name' => 'Khối 8'],
            ['id' => 4, 'name' => 'Khối 9'],
        ];

        $classes = [
            ['id' => 1, 'name' => '6A', 'block_id' => 1, 'teacher_id' => 1],
            ['id' => 2, 'name' => '7A', 'block_id' => 2, 'teacher_id' => 2],
            ['id' => 3, 'name' => '8A', 'block_id' => 3, 'teacher_id' => 3],
            ['id' => 4, 'name' => '9A', 'block_id' => 4, 'teacher_id' => 4],
        ];

        $subjects = [
            ['id' => 1, 'name' => 'Chào cờ', 'max_periods' => 1],
            ['id' => 2, 'name' => 'Sinh hoạt lớp', 'max_periods' => 1],
            ['id' => 3, 'name' => 'Toán', 'max_periods' => 20],
            ['id' => 4, 'name' => 'Lý', 'max_periods' => 20],
            ['id' => 5, 'name' => 'Hóa', 'max_periods' => 20, 'block_id' => [3, 4]], // chỉ dành cho khối 8 và 9
            ['id' => 6, 'name' => 'Văn', 'max_periods' => 20],
            ['id' => 7, 'name' => 'Anh', 'max_periods' => 20],
            ['id' => 8, 'name' => 'Sử', 'max_periods' => 20],
            ['id' => 9, 'name' => 'Địa', 'max_periods' => 20],
            ['id' => 10, 'name' => 'GDCD', 'max_periods' => 20],
            ['id' => 11, 'name' => 'Thể dục', 'max_periods' => 20],
            ['id' => 12, 'name' => 'Âm nhạc', 'max_periods' => 20],
            ['id' => 13, 'name' => 'Mỹ thuật', 'max_periods' => 20],
            ['id' => 14, 'name' => 'Công nghệ', 'max_periods' => 20],
            ['id' => 15, 'name' => 'Tin học', 'max_periods' => 20],
            ['id' => 16, 'name' => 'GDQP', 'max_periods' => 20],
        ];

//        $subjects = [
//            [
//                'id' => 1,
//                'name' => 'Chào cờ',
//                'periods' => [
//                    'morning' => 1,
//                    'afternoon' => 0,
//                ]
//            ],
//            [
//                'id' => 2,
//                'name' => 'Sinh hoạt lớp',
//                'periods' => [
//                    'morning' => 1,
//                    'afternoon' => 0,
//                ]
//            ],
//            [
//                'id' => 3,
//                'name' => 'Toán',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 4,
//                'name' => 'Lý',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 5,
//                'name' => 'Hóa',
//                'periods' => [
//                    'morning' => 4,
//                    'afternoon' => 3,
//                ]
//            ],
//            [
//                'id' => 6,
//                'name' => 'Văn',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 7,
//                'name' => 'Anh',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 8,
//                'name' => 'Sử',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 9,
//                'name' => 'Địa',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 10,
//                'name' => 'GDCD',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 11,
//                'name' => 'Thể dục',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 12,
//                'name' => 'Âm nhạc',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 13,
//                'name' => 'Mỹ thuật',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 14,
//                'name' => 'Công nghệ',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 15,
//                'name' => 'Tin học',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//            [
//                'id' => 16,
//                'name' => 'GDQP',
//                'periods' => [
//                    'morning' => 5,
//                    'afternoon' => 4,
//                ]
//            ],
//        ];

        $teachers = [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'periods_per_week' => 19,
                'subject_ids' => [3, 4],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 2,
                'name' => 'Nguyễn Văn B',
                'periods_per_week' => 19,
                'subject_ids' => [8, 6],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 3,
                'name' => 'Nguyễn Văn C',
                'periods_per_week' => 19,
                'subject_ids' => [5],
                'block_ids' => [3, 4],
            ],
            [
                'id' => 4,
                'name' => 'Nguyễn Văn D',
                'periods_per_week' => 19,
                'subject_ids' => [7],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 5,
                'name' => 'Nguyễn Văn E',
                'periods_per_week' => 19,
                'subject_ids' => [3],
                'block_ids' => [2],
            ],
            [
                'id' => 6,
                'name' => 'Nguyễn Văn F',
                'periods_per_week' => 19,
                'subject_ids' => [6],
                'block_ids' => [2],
            ],
            [
                'id' => 7,
                'name' => 'Nguyễn Văn G',
                'periods_per_week' => 19,
                'subject_ids' => [8],
                'block_ids' => [2],
            ],
            [
                'id' => 8,
                'name' => 'Nguyễn Văn H',
                'periods_per_week' => 19,
                'subject_ids' => [7],
                'block_ids' => [2],
            ],
            [
                'id' => 9,
                'name' => 'Nguyễn Văn I',
                'periods_per_week' => 19,
                'subject_ids' => [3],
                'block_ids' => [3],
            ],
            [
                'id' => 10,
                'name' => 'Nguyễn Văn K',
                'periods_per_week' => 19,
                'subject_ids' => [6],
                'block_ids' => [3],
            ],
            [
                'id' => 11,
                'name' => 'Nguyễn Văn L',
                'periods_per_week' => 19,
                'subject_ids' => [8],
                'block_ids' => [3],
            ],
            [
                'id' => 12,
                'name' => 'Nguyễn Văn M',
                'periods_per_week' => 19,
                'subject_ids' => [7],
                'block_ids' => [3],
            ],
            [
                'id' => 13,
                'name' => 'Nguyễn Văn N',
                'periods_per_week' => 19,
                'subject_ids' => [3],
                'block_ids' => [4],
            ],
            [
                'id' => 14,
                'name' => 'Nguyễn Văn O',
                'periods_per_week' => 19,
                'subject_ids' => [6],
                'block_ids' => [4],
            ],
            [
                'id' => 15,
                'name' => 'Nguyễn Văn P',
                'periods_per_week' => 19,
                'subject_ids' => [4],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 16,
                'name' => 'Nguyễn Văn Q',
                'periods_per_week' => 19,
                'subject_ids' => [5],
                'block_ids' => [1, 2],
            ],
            [
                'id' => 17,
                'name' => 'Nguyễn Văn R',
                'periods_per_week' => 19,
                'subject_ids' => [9],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 18,
                'name' => 'Nguyễn Văn S',
                'periods_per_week' => 19,
                'subject_ids' => [10],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 19,
                'name' => 'Nguyễn Văn T',
                'periods_per_week' => 19,
                'subject_ids' => [11],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 20,
                'name' => 'Nguyễn Văn U',
                'periods_per_week' => 19,
                'subject_ids' => [12],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 21,
                'name' => 'Nguyễn Văn V',
                'periods_per_week' => 19,
                'subject_ids' => [13],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 22,
                'name' => 'Nguyễn Văn W',
                'periods_per_week' => 19,
                'subject_ids' => [14],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 23,
                'name' => 'Nguyễn Văn X',
                'periods_per_week' => 19,
                'subject_ids' => [15],
                'block_ids' => [1, 2, 3, 4],
            ],
            [
                'id' => 24,
                'name' => 'Nguyễn Văn Y',
                'periods_per_week' => 19,
                'subject_ids' => [16],
                'block_ids' => [1, 2, 3, 4],
            ],
        ];

        $dailyPeriods = [
            'Monday' => [
                'morning' => [
                    ['class_ids' => [1, 3], 'periods' => 4],
                    ['class_ids' => [2, 4], 'periods' => 5],
                ],
                'afternoon' => [
                    ['class_ids' => [1, 2], 'periods' => 3],
                    ['class_ids' => [3, 4], 'periods' => 4],
                ],
            ],
            'Tuesday' => [
                'morning' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4]
                ],
                'afternoon' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
            ],
            'Wednesday' => [
                'morning' => [
                    ['class_ids' => [1, 2], 'periods' => 4],
                    ['class_ids' => [3, 4], 'periods' => 5],
                ],
                'afternoon' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
            ],
            'Thursday' => [
                'morning' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
                'afternoon' => [],
            ],
            'Friday' => [
                'morning' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
                'afternoon' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
            ],
            'Saturday' => [
                'morning' => [
                    ['class_ids' => [1, 2], 'periods' => 4],
                    ['class_ids' => [3, 4], 'periods' => 5],
                ],
                'afternoon' => [
                    ['class_ids' => [1, 2, 3, 4], 'periods' => 4],
                ],
            ],
        ];

        return $this->createTimetable($classes, $subjects, $teachers, $dailyPeriods);
    }

    public function createTimetable($classes, $subjects, $teachers, $dailyPeriods): array
    {
        $schedule = [];

        foreach ($classes as $cls) {
            $schedule[$cls['id']] = [];

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
            $schedule[$cls['id']]['Monday']['morning'][0] = [
                'subject_id' => 1,
                'subject_name' => 'Chào cờ',
            ];

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
