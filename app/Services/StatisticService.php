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
use App\Http\Resources\ScoreResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


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

            $blockStudentCount = $academicYear->classes->flatMap(function ($class) {
                return $class->blocks->map(function ($block) use ($class) {
                    return [
                        'blockName' => $block->name,
                        'studentCount' => $class->students->count(),
                    ];
                });
            });

            return $blockStudentCount->groupBy('blockName')->map(function ($students, $blockName) {
                return [
                    'blockName' => $blockName,
                    'totalStudents' => $students->sum('studentCount'),
                ];
            })->values();
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
    // học sinh xem chi tiết điểm các môn học của mình
    public function showStudentScoreSemester($classSlug, $semesterSlug, $yearSlug)
    {
        // Lấy thông tin học sinh đăng nhập
        $student = Auth::user();

        // Kiểm tra nếu người dùng không phải là học sinh
        if (!$student || !$student->roles->contains('name', 'student')) {
            throw new Exception("Người dùng không có quyền truy cập.");
        }

        // Lấy thông tin lớp học dựa trên slug
        $class = Classes::where('slug', $classSlug)->first();
        if (!$class) {
            throw new Exception("Không tìm thấy lớp.");
        }

        // Lấy thông tin học kỳ
        $semester = Semester::where('slug', $semesterSlug)->first();
        if (!$semester) {
            throw new Exception("Không tìm thấy học kỳ.");
        }

        // Lấy thông tin năm học
        $academicYear = AcademicYear::where('slug', $yearSlug)->first();
        if (!$academicYear) {
            throw new Exception("Không tìm thấy năm học.");
        }

        // Lấy điểm của học sinh trong lớp và học kỳ cụ thể
        $subjectScores = $student->subjectScores()
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->get();

        // Kiểm tra nếu không có dữ liệu điểm
        if ($subjectScores->isEmpty()) {
            throw new Exception("Không tìm thấy điểm của bạn trong lớp {$class->name}.");
        }

        // Xử lý và định dạng điểm
        $formattedScores = $subjectScores->map(function ($score) {
            $subject = Subject::find($score->subject_id); // Lấy thông tin môn học

            // Kiểm tra và xử lý detailed_scores
            $detailedScores = is_string($score->detailed_scores)
                ? json_decode($score->detailed_scores, true)
                : $score->detailed_scores;


            $formattedScores = [
                'diemMieng' => $detailedScores['diem_mieng'] ?? [],
                'diem15Phut' => $detailedScores['diem_15_phut'] ?? [],
                'diemCuoiKi' => $detailedScores['diem_cuoi_ki'] ?? [],
                'diemGiuaKi' => $detailedScores['diem_giua_ki'] ?? [],
                'diemMotTiet' => $detailedScores['diem_mot_tiet'] ?? [],
            ];

            return [
                'subjectName' => $subject->name ?? 'N/A',
                'detailedScores' => $formattedScores,
                'averageScore' => $score->average_score
            ];
        });

        // Trả về dữ liệu
        return (object)[
            'studentName' => $student->name,
            'username' => $student->username,
            'class' => $class->name,
            'semester' => $semester->name,
            'scores' => $formattedScores
        ];
    }




    // giáo viên xem chi tiết điểm các môn học của học sinh
    public function showStudentScoreSemesterClass($classSlug, $semesterSlug, $yearSlug)
    {
        // Lấy thông tin giáo viên đăng nhập
        $teacher = Auth::user();

        if (!$teacher) {
            throw new Exception("Người dùng chưa đăng nhập.");
        }

        // Lấy thông tin lớp dựa trên slug
        $class = Classes::where('slug', $classSlug)
            ->where('teacher_id', $teacher->id) // Kiểm tra giáo viên chủ nhiệm
            ->first();

        if (!$class) {
            throw new Exception("Bạn không phải là giáo viên chủ nhiệm của lớp này hoặc lớp không tồn tại.");
        }

        // Lấy thông tin học kỳ
        $semester = Semester::where('slug', $semesterSlug)->first();
        if (!$semester) {
            throw new Exception("Học kỳ không tìm thấy!");
        }

        // Lấy thông tin năm học
        $academicYear = AcademicYear::where('slug', $yearSlug)->first();
        if (!$academicYear) {
            throw new Exception("Năm học không tìm thấy!");
        }

        // Lấy điểm của tất cả học sinh trong lớp đó
        $subjectScores = DB::table('subject_scores')
            ->join('users', 'subject_scores.student_id', '=', 'users.id') // Kết nối bảng users
            ->join('subjects', 'subject_scores.subject_id', '=', 'subjects.id') // Kết nối bảng subjects
            ->select(
                'users.name as student_name',
                'subjects.name as subject_name',
                'subject_scores.detailed_scores',
                'subject_scores.average_score',
            )
            ->where('subject_scores.class_id', $class->id)
            ->where('subject_scores.semester_id', $semester->id)
            ->get();

        if ($subjectScores->isEmpty()) {
            throw new Exception("Không tìm thấy điểm của lớp {$class->name} trong học kỳ này.");
        }

        // Xử lý dữ liệu detailed_scores và đổi tên key
        $processedScores = $subjectScores->map(function ($score) use ($semester) {
            $detailedScores = json_decode($score->detailed_scores, true);

    

            $formattedScores = [
                'diemMieng' => $detailedScores['diem_mieng'] ?? [],
                'diem15Phut' => $detailedScores['diem_15_phut'] ?? [],
                'diemCuoiKi' => $detailedScores['diem_cuoi_ki'] ?? [],
                'diemGiuaKi' => $detailedScores['diem_giua_ki'] ?? [],
                'diemMotTiet' => $detailedScores['diem_mot_tiet'] ?? [],
            ];

            return [
                'studentName' => $score->student_name,
                'subjectName' => $score->subject_name ?? 'N/A',
                'semesterName' => $semester->name,
                'detailedScores' => $formattedScores,
                'averageScore' => $score->average_score,
            ];
        });

        // Trả về dữ liệu
        return (object)[
            'teacherName' => $teacher->name,
            'username' => $teacher->username,
            'class' => $class->name,
            'scores' => $processedScores
        ];
    }

    // hàm tính điểm tổng kết cuối năm dành cho giáo viên chủ nhiệm(cả lớp), có cả điểm theo kì.
    public function calculateFinalScoreYearClass($classSlug, $yearSlug)
    {
        $teacher = Auth::user();

        if (!$teacher) {
            throw new Exception("Người dùng chưa đăng nhập.");
        }

        // Lấy thông tin lớp và năm học
        $class = Classes::where('slug', $classSlug)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$class) {
            throw new Exception("Bạn không phải là giáo viên chủ nhiệm của lớp này hoặc lớp không tồn tại.");
        }

        $academicYear = AcademicYear::where('slug', $yearSlug)->first();
        if (!$academicYear) {
            throw new Exception("Năm học không tìm thấy!");
        }

        $semesters = $academicYear->semesters;

        if ($semesters->isEmpty()) {
            throw new Exception("Năm học này không có kỳ học nào.");
        }

        $students = $class->students;
        $subjects = $class->subjects;

        if ($students->isEmpty()) {
            throw new Exception("Không có học sinh trong lớp này.");
        }

        $finalScores = [];

        foreach ($students as $student) {
            $semesterAverages = [];
            $semesterPerformance = []; // Học lực của từng kỳ
            $hasValidScoresForYear = true;

            foreach ($semesters as $semester) {
                $scores = DB::table('subject_scores')
                    ->where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('semester_id', $semester->id)
                    ->pluck('average_score', 'subject_id');

                // Kiểm tra học sinh có đủ điểm của tất cả các môn thuộc lớp không
                $allSubjectsHaveScores = $subjects->pluck('id')->diff($scores->keys())->isEmpty();

                if ($allSubjectsHaveScores) {
                    // Tính điểm trung bình kỳ học
                    $averageScore = round(array_sum($scores->toArray()) / count($scores), 2);
                    $semesterAverages[$semester->id] = $averageScore;
                    $semesterPerformance[$semester->id] = $this->determinePerformanceLevel($averageScore);

                    // Lưu điểm và học lực kỳ vào bảng final_scores
                    DB::table('final_scores')->updateOrInsert(
                        [
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYear->id,
                            'semester_id' => $semester->id,
                        ],
                        [
                            'average_score' => $averageScore,
                            'performance_level' => $semesterPerformance[$semester->id],
                            'class_id' => $class->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    $semesterAverages[$semester->name] = null;
                    $semesterPerformance[$semester->name] = "Chưa đủ điểm để xét học lực";
                    $hasValidScoresForYear = false; // không đủ điểm cho năm học
                }
            }

            // Tính điểm tổng kết năm học nếu đủ điểm cả hai kỳ
            if ($hasValidScoresForYear) {
                $validAverages = array_filter($semesterAverages, fn($score) => $score !== null);
                $finalScoreYear = !empty($validAverages) ? round(array_sum($validAverages) / count($validAverages), 2) : 0;
                $finalPerformance = $this->determinePerformanceLevel($finalScoreYear);

                // Lưu điểm tổng kết năm vào bảng final_scores
                DB::table('final_scores')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'class_id' => $class->id,
                        'semester_id' => null, // tổng kết cuối năm sẽ không có semester
                    ],
                    [
                        'average_score' => $finalScoreYear,
                        'performance_level' => $finalPerformance,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            } else {
                $finalScoreYear = null;
                $finalPerformance = "Chưa đủ điểm để xét học lực";
            }

            $finalScores[] = [
                'studentName' => $student->name,
                'username' => $student->username,
                'semesterScores' => $semesterAverages,
                'semesterPerformance' => $semesterPerformance,
                'yearScore' => $finalScoreYear,
                'yearPerformance' => $finalPerformance,
            ];
        }

        return (object) [
            'teacherName' => $teacher->name,
            'username' => $teacher->username,
            'class' => $class->name,
            'academicYear' => $academicYear->name,
            'finalScores' => $finalScores,
        ];
    }


    // Hàm xác định học lực
    private function determinePerformanceLevel($averageScore)
    {
        if ($averageScore >= 8.5) {
            return 'Giỏi';
        } elseif ($averageScore >= 6.5) {
            return 'Khá';
        } elseif ($averageScore >= 5.0) {
            return 'Trung bình';
        } else {
            return 'Yếu';
        }
    }
}
