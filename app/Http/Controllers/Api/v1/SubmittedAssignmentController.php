<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmittedAssignmentRequest;
use App\Http\Resources\SubmittedAssignmentCollection;
use App\Http\Resources\SubmittedAssignmentResource;
use App\Models\SubmittedAssignment;
use App\Services\SubmittedAssignmentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

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

            if ($submittedAssignment->isEmpty())
            {
                return $this->errorResponse('Dữ liệu không tồn tại', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new SubmittedAssignmentCollection($submittedAssignment),
                'Lấy thông tin thành côg',
                Response::HTTP_OK
            );

        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(SubmittedAssignmentRequest $request)
    {
        try {
            $data = $request->validated();

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


}
