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

    public function getScoreByClassAcademicYearSemester(Request $request): JsonResponse
    {
        try {
            $class_query = $request->query('class');
            $semester_query = $request->query('semester');

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
            $students = $class->students->map(function ($student) use ($scores, $subjects) {
                $studentScores = $scores->where('student_id', $student->id);

                if ($studentScores->isEmpty()) {
                    return $subjects->map(function ($subject) use ($student) {
                        return [
                            'studentName' => $student->name,
                            'studentUsername' => $student->username,
                            'studentImage' => $student->image,
                            'subjectName' => $subject->name,
                            'academicYearName' => null,
                            'semesterName' => null,
                            'className' => null,
                            'mouthPoints' => null,
                            'fifteenMinutesPoints' => null,
                            'onePeriodPoints' => null,
                            'midSemesterPoints' => null,
                            'endSemesterPoints' => null,
                            'averageScore' => null,
                        ];
                    });
                }

                return $studentScores->map(function ($score) use ($student, $subjects) {
                    $subject = Subject::find($score->subject_id);
                    $semester = Semester::find($score->semester_id);
                    $academicYear = AcademicYear::find($semester->academic_year_id);
                    $class = Classes::find($score->class_id);

                    return $subjects->map(function ($subject) use ($student, $score, $academicYear, $semester, $class) {
                        return [
                            'studentName' => $student->name,
                            'studentUsername' => $student->username,
                            'studentImage' => $student->image,
                            'subjectName' => $subject->name,
                            'academicYearName' => $academicYear->name,
                            'semesterName' => $semester->name,
                            'className' => $class->name,
                            'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                            'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                            'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                            'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                            'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                            'averageScore' => $score->average_score,
                        ];
                    });
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

    public function getScoreByStudent(Request $request): JsonResponse
    {
        try {
            $class_query = $request->query('class');
            $semester_query = $request->query('semester');

            $student = Auth::user();

            $checkStudent = Role::where('slug', 'student')->first();

            if (!$student->roles->contains($checkStudent)) {
                throw new Exception('Học sinh chưa đăng nhập');
            }

            if (!$student) {
                throw new Exception('Học sinh không tồn tại');
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
            $subjects = $subjects->map(function ($subject) use ($scores) {
                $score = $scores->where('subject_id', $subject->id)->first();

                if (!$score) {
                    return [
                        'subjectName' => $subject->name,
                        'academicYearName' => null,
                        'semesterName' => null,
                        'className' => null,
                        'mouthPoints' => null,
                        'fifteenMinutesPoints' => null,
                        'onePeriodPoints' => null,
                        'midSemesterPoints' => null,
                        'endSemesterPoints' => null,
                        'averageScore' => null,
                    ];
                }

                $semester = Semester::find($score->semester_id);
                $academicYear = AcademicYear::find($semester->academic_year_id);
                $class = Classes::find($score->class_id);

                return [
                    'subjectName' => $subject->name,
                    'academicYearName' => $academicYear->name,
                    'semesterName' => $semester->name,
                    'className' => $class->name,
                    'mouthPoints' => $score->detailed_scores['diem_mieng']['score'] ?? null,
                    'fifteenMinutesPoints' => $score->detailed_scores['diem_15_phut']['score'] ?? null,
                    'onePeriodPoints' => $score->detailed_scores['diem_mot_tiet']['score'] ?? null,
                    'midSemesterPoints' => $score->detailed_scores['diem_giua_ki']['score'] ?? null,
                    'endSemesterPoints' => $score->detailed_scores['diem_cuoi_ki']['score'] ?? null,
                    'averageScore' => $score->average_score,
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
