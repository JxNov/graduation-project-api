<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmittedAssignmentRequest;
use App\Http\Resources\SubmittedAssignmentCollection;
use App\Http\Resources\SubmittedAssignmentResource;
use App\Models\Assignment;
use App\Models\SubmittedAssignment;
use App\Services\SubmittedAssignmentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        }
        catch (Exception $e) {
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
        }
        catch (Exception $e) {
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
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function showAssignmentStudent()
{
    try {
        $student = Auth::user(); // Lấy thông tin học sinh hiện tại
        $classes = $student->classes; // Lấy danh sách các lớp mà học sinh đang học

        if ($classes->isEmpty()) {
            throw new Exception('Học sinh này không có lớp hoặc lớp đã bị xoá');
        }

        // Lấy tất cả các assignment của các lớp mà học sinh học
        $assignments = Assignment::whereIn('class_id', $classes->pluck('id'))
            ->whereHas('submittedAssignments', function ($query) use ($student) {
                $query->where('student_id', $student->id); // Chỉ lấy bài đã nộp của học sinh này
            })
            ->with(['submittedAssignments' => function ($query) use ($student) {
                $query->where('student_id', $student->id); // Kèm theo bài nộp của học sinh này
            }])
            ->get();

        if ($assignments->isEmpty()) {
            throw new Exception('Không có bài tập nào được nộp bởi học sinh này.');
        }

        // Log thông tin để kiểm tra
        // Log::info('Classes: ', $classes->toArray());
        // Log::info('Assignments: ', $assignments->toArray());

        // Trả về dữ liệu
        return response()->json([
            'status' => 'success',
            'data' => $assignments,
        ], Response::HTTP_OK);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}

}
