<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScoreRequest;
use App\Http\Resources\ScoreCollection;
use App\Http\Resources\ScoreResource;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Role;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Models\Semester;
use App\Services\ScoreService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    use ApiResponseTrait;

    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    public function index()
    {
        $scores = Score::select('id', 'student_id', 'subject_id', 'semester_id', 'average_score')
            ->latest('id')
            ->paginate(6);

        if ($scores->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new ScoreCollection($scores),
            'Lấy tất cả thông tin điểm thành công',
            Response::HTTP_OK
        );
    }

    public function store(ScoreRequest $request)
    {
        try {
            $data = $request->validated();

            $score = $this->scoreService->saveOrUpdateScore($data);

            return $this->successResponse(
                $score,
                'Lưu điểm cho học sinh thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        $score = Score::find($id);

        if (!$score) {
            return $this->errorResponse('Điểm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new ScoreResource($score),
            'Lấy thông tin điểm thành công',
            Response::HTTP_OK
        );
    }

    //Truy vấn dựa trên username, id_subject và theo id_semester
    public function getScoreByStudentSubjectSemester($student_name, $subject_slug, $class_slug, $semester_slug)
    {
        try {
            // Gọi hàm từ ScoreService
            $score = $this->scoreService->getScoreByStudentSubjectClassSemester($student_name, $subject_slug, $class_slug, $semester_slug);

            return $this->successResponse(
                new ScoreResource($score),
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


    // public function update(ScoreRequest $request, $id)
    // {
    //     try {
    //         $data = $request->validated();

    //         $score = $this->scoreService->updateScore($id, $data);

    //         return $this->successResponse(
    //             new ScoreResource($score),
    //             'Cập nhật điểm thành công',
    //             Response::HTTP_OK
    //         );
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function getScoreByAdmin(Request $request): JsonResponse
    {
        try {
            $academic_year_query = $request->query('academicYear');
            $class_query = $request->query('class');
            $semester_query = $request->query('semester');

            $admin = Auth::user();

            $checkAdmin = Role::where('slug', 'admin')->first();

            if (!$admin || !$admin->roles->contains($checkAdmin)) {
                throw new Exception('Admin chưa đăng nhập hoặc không phải admin');
            }

            $academic_year = AcademicYear::where('slug', $academic_year_query)->first();
            if (!$academic_year) {
                throw new Exception('Năm học không tồn tại');
            }

            $class = Classes::where('slug', $class_query)->first();
            if (!$class) {
                throw new Exception('Lớp học không tồn tại');
            }

            $semester = Semester::where('slug', $semester_query)->first();
            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại');
            }

            $scores = Score::where('class_id', $class->id)
                ->where('semester_id', $semester->id)
                ->get();

            $subjects = Subject::all();

            $students = $class->students->map(function ($student) use ($scores, $subjects, $semester, $class, $academic_year) {
                return $subjects->map(function ($subject) use ($student, $scores, $semester, $class, $academic_year) {
                    $score = $scores->where('student_id', $student->id)->where('subject_id', $subject->id)->first();

                    return [
                        'studentName' => $student->name,
                        'studentUsername' => $student->username,
                        'studentImage' => $student->image,
                        'subjectName' => $subject->name,
                        'academicYearName' => $academic_year->name,
                        'semesterName' => $semester->name,
                        'className' => $class->name,
                        'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                        'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                        'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                        'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                        'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                        'averageScore' => $score->average_score ?? null,
                    ];
                });
            })->flatten(1);

            return $this->successResponse(
                $students,
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getScoreByTeacher(Request $request): JsonResponse
    {
        try {
            $academic_year_query = $request->query('academicYear');
            $class_query = $request->query('class');
            $semester_query = $request->query('semester');
            $subject_query = $request->query('subject');

            $teacher = Auth::user();

            $checkTeacher = Role::where('slug', 'teacher')->first();

            if (!$teacher || !$teacher->roles->contains($checkTeacher)) {
                throw new Exception('Giáo viên chưa đăng nhập hoặc không phải giáo viên');
            }

            $academic_year = AcademicYear::where('slug', $academic_year_query)->first();
            if (!$academic_year) {
                throw new Exception('Năm học không tồn tại');
            }

            $class = Classes::where('slug', $class_query)->first();
            if (!$class) {
                throw new Exception('Lớp học không tồn tại');
            }

            $checkClass = $teacher->teachingClasses->contains($class);
            if (!$checkClass) {
                throw new Exception('Giáo viên không dạy lớp này');
            }

            $semester = Semester::where('slug', $semester_query)->first();
            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại');
            }

            $isClassTeacher = Classes::where('teacher_id', $teacher->id)->where('id', $class->id)->exists();
            if ($isClassTeacher) {
                $subjects = Subject::all();

                $scores = Score::where('class_id', $class->id)
                    ->where('semester_id', $semester->id)
                    ->get();

                $students = $class->students->map(function ($student) use ($scores, $subjects, $semester, $class, $academic_year) {
                    return $subjects->map(function ($subject) use ($student, $scores, $semester, $class, $academic_year) {
                        $score = $scores->where('student_id', $student->id)->where('subject_id', $subject->id)->first();

                        return [
                            'studentName' => $student->name,
                            'studentUsername' => $student->username,
                            'studentImage' => $student->image,
                            'subjectName' => $subject->name,
                            'academicYearName' => $academic_year->name,
                            'semesterName' => $semester->name,
                            'className' => $class->name,
                            'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                            'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                            'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                            'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                            'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                            'averageScore' => $score->average_score ?? null,
                        ];
                    });
                })->flatten(1);

                return $this->successResponse(
                    $students,
                    'Lấy điểm thành công',
                    Response::HTTP_OK
                );
            }

            $subject = Subject::where('slug', $subject_query)->first();

            if (!$subject) {
                throw new Exception('Môn học không tồn tại');
            }

            $checkSubject = $teacher->subjects->contains($subject);
            if (!$checkSubject) {
                throw new Exception('Giáo viên không dạy môn học này');
            }

            $scores = Score::where('class_id', $class->id)
                ->where('semester_id', $semester->id)
                ->get();

            $students = $class->students->map(function ($student) use ($scores, $subject, $semester, $class, $academic_year) {
                $score = $scores->where('student_id', $student->id)->where('subject_id', $subject->id)->first();

                return [
                    'studentName' => $student->name,
                    'studentUsername' => $student->username,
                    'studentImage' => $student->image,
                    'subjectName' => $subject->name,
                    'academicYearName' => $academic_year->name,
                    'semesterName' => $semester->name,
                    'className' => $class->name,
                    'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                    'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                    'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                    'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                    'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                    'averageScore' => $score->average_score ?? null,
                ];
            });

            return $this->successResponse(
                $students,
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getScoreByStudent(Request $request): JsonResponse
    {
        try {
            $class_query = $request->query('class');
            $semester_query = $request->query('semester');

            $student = Auth::user();

            $checkStudent = Role::where('slug', 'student')->first();

            if (!$student || !$student->roles->contains($checkStudent)) {
                throw new Exception('Học sinh chưa đăng nhập hoặc không phải học sinh');
            }

            $class = Classes::where('slug', $class_query)->first();
            if (!$class) {
                throw new Exception('Lớp học không tồn tại');
            }

            $semester = Semester::where('slug', $semester_query)->first();
            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại');
            }

            $scores = Score::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('semester_id', $semester->id)
                ->get();

            $subjects = Subject::all();
            $academic_year = AcademicYear::find($semester->academic_year_id);

            $subjects = $subjects->map(function ($subject) use ($semester, $academic_year, $scores, $class) {
                $score = $scores->where('subject_id', $subject->id)->first();

                if (!$score) {
                    return [
                        'subjectName' => $subject->name,
                        'academicYearName' => $academic_year->name,
                        'semesterName' => $semester->name,
                        'className' => $class->name,
                        'mouthPoints' => null,
                        'fifteenMinutesPoints' => null,
                        'onePeriodPoints' => null,
                        'midSemesterPoints' => null,
                        'endSemesterPoints' => null,
                        'averageScore' => null,
                    ];
                }

                return [
                    'subjectName' => $subject->name,
                    'academicYearName' => $academic_year->name,
                    'semesterName' => $semester->name,
                    'className' => $class->name,
                    'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                    'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                    'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                    'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                    'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                    'averageScore' => $score->average_score ?? null,
                ];
            });

            return $this->successResponse(
                $subjects,
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
