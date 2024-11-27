<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmittedAssignmentRequest;
use App\Http\Resources\SubmittedAssignmentCollection;
use App\Http\Resources\SubmittedAssignmentResource;
use App\Models\Assignment;
use App\Models\Classes;
use App\Models\SubmittedAssignment;
use App\Services\SubmittedAssignmentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;


class SubmittedAssignmentController extends Controller
{
    use ApiResponseTrait;

    private $submittedAssignmentService;

    public function __construct(SubmittedAssignmentService $submittedAssignmentService)
    {
        $this->submittedAssignmentService = $submittedAssignmentService;
    }

    public function index()
    {
        try {
            $submittedAssignment = SubmittedAssignment::latest('id')
                ->select('assignment_id', 'student_id', 'file_path', 'score', 'feedback', 'submitted_at')
                ->with(['assignment', 'student'])
                ->paginate(10);

            if ($submittedAssignment->isEmpty()) {
                return $this->errorResponse('Dữ liệu không tồn tại', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new SubmittedAssignmentCollection($submittedAssignment),
                'Lấy thông tin thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(SubmittedAssignmentRequest $request, $assignmentSlug)
    {
        try {
            $data = $request->validated();

            $assignment = Assignment::where('slug', $assignmentSlug)->first();

            if (!$assignment) {
                throw new Exception('Bài tập không tồn tại');
            }

            $data['assignment_id'] = $assignment->id;

            $submittedAssignment = $this->submittedAssignmentService->createOrUpdateSubmittedAssignment($data);

            return $this->successResponse(
                new SubmittedAssignmentResource($submittedAssignment),
                'Tạo mới bài nộp thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


    //controller để dành cho giáo viên chấm điểm và give feedback
    public function updateScoreAndFeedback(SubmittedAssignmentRequest $request, $assignmentSlug)
    {
        try {
            $data = $request->validated();

            $user = $request->user();

            $submittedAssignment = $this->submittedAssignmentService->updateScoreAndFeedback(
                $assignmentSlug,
                $data['score'],
                $data['feedback'],
                $user->username
            );

            return $this->successResponse(
                new SubmittedAssignmentResource($submittedAssignment),
                'Chấm điểm và gửi phản hồi thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function showAssignmentStudent()
    {
        try {
            $student = Auth::user(); // Lấy thông tin học sinh hiện tại
            if (!$student->roles->contains('name', 'student')) {
                throw new Exception("Người dùng chưa đăng nhập.");
            }
            $classes = $student->classes; // Lấy danh sách các lớp mà học sinh đang học

            if ($classes->isEmpty()) {
                throw new Exception('Học sinh này không có lớp hoặc lớp đã bị xoá');
            }

            // Lấy tất cả các assignment của các lớp mà học sinh học
            $assignments = Assignment::whereIn('class_id', $classes->pluck('id'))
                ->with(['submittedAssignments' => function ($query) use ($student) {
                    $query->where('student_id', $student->id); // Kèm theo bài nộp của học sinh này
                }])
                ->get();

            // Định dạng lại dữ liệu assignment để phân loại đã làm và chưa làm
            $assignmentsData = $assignments->map(function ($assignment) use ($student) {
                // Kiểm tra xem học sinh đã nộp bài này chưa
                $submitted = $assignment->submittedAssignments->first();

                return [
                    'assignment_id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'submitted' => $submitted ? true : false, // Đã nộp hay chưa
                    'submission' => $submitted ? [
                        'file' => $submitted->file_path,
                        'feedback' => $submitted->feedback,
                        'submitted_at' => $submitted->created_at,
                    ] : null, // Thông tin chi tiết bài nộp
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $assignmentsData,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function showAssignmentsForTeacher($classSlug)
    {
        try {
            $teacher = Auth::user();

            // Kiểm tra nếu giáo viên không có vai trò 'teacher'
            if (!$teacher->roles->contains('name', 'teacher')) {
                throw new Exception("Bạn không có quyền xem thông tin này.");
            }

            // Lấy lớp học theo slug
            $class = Classes::where('slug', $classSlug)->first();
            if (!$class) {
                throw new Exception('Lớp không tồn tại.');
            }

            // Kiểm tra giáo viên có dạy lớp này không
            if ($teacher->classTeachers && !$teacher->classTeachers->contains('id', $class->id)) {
                throw new Exception('Bạn không có quyền xem thông tin lớp này.');
            }

            // Lấy danh sách các bài tập đã giao cho lớp này bởi giáo viên (teacher_id)
            $assignments = Assignment::where('class_id', $class->id)
                ->where('teacher_id', $teacher->id)  // Chỉ lấy bài tập của giáo viên này
                ->with(['submittedAssignments.student'])
                ->get();

            if ($assignments->isEmpty()) {
                throw new Exception('Không có bài tập nào cho lớp này từ giáo viên.');
            }

            $students = $class->students;

            // Định dạng dữ liệu bài tập và học sinh đã nộp/chưa nộp
            $assignmentsData = $assignments->map(function ($assignment) use ($students) {
                $submittedStudents = $assignment->submittedAssignments->pluck('student_id');
                $studentsData = $students->map(function ($student) use ($submittedStudents, $assignment) {
                    $isSubmitted = $submittedStudents->contains($student->id);
                    $submission = $isSubmitted
                        ? $assignment->submittedAssignments->firstWhere('student_id', $student->id)
                        : null;

                    return [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'username'=>$student->username,
                        'submitted' => $isSubmitted,
                        'submission' => $submission ? [
                            'file' => $submission->file_path,
                            'feedback' => $submission->feedback,
                            'submitted_at' => $submission->created_at,
                        ] : null,
                    ];
                });

                return [
                    'assignment_id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'students' => $studentsData,
                ];
            });

            return response()->json([
                'status' => 'success',
                'class' => [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'slug' => $class->slug,
                ],
                'assignments' => $assignmentsData,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
