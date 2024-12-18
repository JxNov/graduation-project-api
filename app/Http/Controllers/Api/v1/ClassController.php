<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassRequest;
use App\Http\Resources\ClassCollection;
use App\Http\Resources\ClassResource;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Classes;
use App\Models\Score;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClassController extends Controller
{
    use ApiResponseTrait;

    protected $classService;

    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    public function index()
    {
        $classes = Classes::select('id', 'name', 'slug', 'code', 'teacher_id')
            ->latest('id')
            ->with(['teacher', 'academicYears', 'blocks'])
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new ClassCollection($classes),
            'Lấy tất cả thông tin lớp học thành công',
            Response::HTTP_OK
        );
    }

    public function store(ClassRequest $request)
    {
        try {
            $data = $request->validated();

            $class = $this->classService->createNewClass($data);

            return $this->successResponse(
                new ClassResource($class),
                'Thêm lớp học thành công',
                Response::HTTP_CREATED
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show(Request $request, $slug)
    {
        $subjectQuery = $request->query('subject');
        $semesterQuery = $request->query('semester');

        $class = Classes::where('slug', $slug)
            ->with(['teacher', 'academicYears', 'blocks', 'students', 'subjects'])
            ->first();

        if ($class === null) {
            return $this->errorResponse(
                'Không tìm thấy lớp học',
                Response::HTTP_NOT_FOUND
            );
        }

        $scoresQuery = Score::where('class_id', $class->id)->with('student');

        if ($semesterQuery) {
            $semester = Semester::where('slug', $semesterQuery)->first();
            if ($semester === null) {
                return $this->errorResponse(
                    'Không tìm thấy kỳ học',
                    Response::HTTP_NOT_FOUND
                );
            }
            $scoresQuery->where('semester_id', $semester->id);
        }

        if ($subjectQuery) {
            $subject = Subject::where('slug', $subjectQuery)->first();
            if ($subject === null) {
                return $this->errorResponse(
                    'Không tìm thấy môn học',
                    Response::HTTP_NOT_FOUND
                );
            }
            $scoresQuery->where('subject_id', $subject->id);
        }

        $scores = $scoresQuery->get();

        $data = [
            'name' => $class->name,
            'slug' => $class->slug,
            'code' => $class->code,
            'teacherName' => optional($class->teacher)->name,
            'teacherImage' => optional($class->teacher)->image,
            'username' => optional($class->teacher)->username,
            'academicYearSlug' => $class->academicYears->pluck('slug')->first(),
            'blockSlug' => $class->blocks->pluck('slug')->first(),
            'numberOfStudents' => $class->students->count(),
            'students' => $class->students->map(function ($student) use ($scores) {
                $studentScore = $scores->where('student_id', $student->id)->first();
                $mouthPoints = $studentScore->detailed_scores['diem_mieng']['score'] ?? null;
                $fifteenMinutesPoints = $studentScore->detailed_scores['diem_15_phut']['score'] ?? null;
                $onePeriodPoints = $studentScore->detailed_scores['diem_mot_tiet']['score'] ?? null;
                $midSemesterPoints = $studentScore->detailed_scores['diem_giua_ki']['score'] ?? null;
                $endSemesterPoints = $studentScore->detailed_scores['diem_cuoi_ki']['score'] ?? null;
                $averageScore = $studentScore->average_score ?? null;

                return [
                    'name' => $student->name,
                    'username' => $student->username,
                    'image' => $student->image,
                    'gender' => $student->gender,
                    'mouthPoints' => $mouthPoints,
                    'fifteenMinutesPoints' => $fifteenMinutesPoints,
                    'onePeriodPoints' => $onePeriodPoints,
                    'midSemesterPoints' => $midSemesterPoints,
                    'endSemesterPoints' => $endSemesterPoints,
                    'averageScore' => $averageScore,
                ];
            }),
        ];

        return $this->successResponse(
            $data,
            'Lấy thông tin lớp học thành công',
            Response::HTTP_OK
        );
    }


    public function update(ClassRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $class = $this->classService->updateClass($data, $slug);

            return $this->successResponse(
                new ClassResource($class),
                'Cập nhật thông tin lớp học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function assignClassToTeacher(Request $request, $slug)
    {
        try {
            $data = $request->validate(
                [
                    'username' => 'required',
                    'username.*' => 'exists:users,username'
                ],
                [
                    'username.required' => 'Hãy chọn giáo viên',
                    'username.exists' => 'Tên giáo viên không tồn tại',
                ]
            );

            $this->classService->assignClassToTeacher($data, $slug);

            return $this->successResponse(
                null,
                'Phân công giáo viên thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getClassOfTeacher($username)
    {
        try {
            $teacher = User::where('username', $username)
                ->first();

            if ($teacher === null) {
                return $this->errorResponse(
                    'Không tìm thấy giáo viên',
                    Response::HTTP_NOT_FOUND
                );
            }

            $classOfTeacher = $teacher->teachingClasses()
                ->select('classes.id', 'classes.name as className', 'classes.slug as classSlug', 'classes.code as classCode', 'classes.teacher_id')
                ->with(['teacher', 'academicYears', 'blocks', 'students'])
                ->get();

            $data = $classOfTeacher->map(function ($class) {
                // \Log::info($class->academicYears->pluck('slug'));
                return [
                    'name' => $class->className,
                    'slug' => $class->classSlug,
                    'code' => $class->classCode,
                    'teacherName' => $class->teacher->name,
                    'teacherUsername' => $class->teacher->username,
                    'academicYearSlug' => $class->academicYears->pluck('slug')->first(),
                    'blockSlug' => $class->blocks->pluck('slug')->first(),
                    'numberOfStudents' => $class->students->count()
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy danh sách lớp của giáo viên thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getClassOfStudent($username)
    {
        try {
            $student = User::where('username', $username)
                ->first();

            if ($student === null) {
                return $this->errorResponse(
                    'Không tìm thấy học sinh',
                    Response::HTTP_NOT_FOUND
                );
            }

            $classOfStudent = $student->classes()
                ->select('classes.id', 'classes.name as className', 'classes.slug as classSlug', 'classes.code as classCode', 'classes.teacher_id')
                ->with(['teacher', 'academicYears', 'blocks', 'students'])
                ->get();

            $data = $classOfStudent->map(function ($class) {
                // \Log::info($class->academicYears->pluck('slug'));
                return [
                    'name' => $class->className,
                    'slug' => $class->classSlug,
                    'code' => $class->classCode,
                    'teacherName' => $class->teacher->name,
                    'teacherUsername' => $class->teacher->username,
                    'academicYearSlug' => $class->academicYears->pluck('slug')->first(),
                    'blockSlug' => $class->blocks->pluck('slug')->first(),
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy danh sách lớp của học sinh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function promoteStudent(ClassRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $newClass = $this->classService->promoteStudent($data, $slug);

            return $this->successResponse(
                new ClassResource($newClass),
                'Lên lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->classService->deleteClass($slug);

            return $this->successResponse(
                null,
                'Xóa lớp học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $classes = Classes::select('id', 'name', 'slug', 'teacher_id')
            ->latest('id')
            ->with('teacher')
            ->onlyTrashed()
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            ClassResource::collection($classes),
            'Lấy tất cả thông tin lớp học đã xóa thành công',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $class = $this->classService->restoreClass($slug);

            return $this->successResponse(
                new ClassResource($class),
                'Phục hồi lớp học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->classService->forceDeleteClass($slug);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn lớp học thành công',
                Response::HTTP_NO_CONTENT
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
