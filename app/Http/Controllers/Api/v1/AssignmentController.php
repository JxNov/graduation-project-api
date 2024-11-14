<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Http\Resources\AssignmentCollection;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Services\AssignmentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    use ApiResponseTrait;

    protected $assignmentService;
    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index()
    {
        try {
            $assignments = Assignment::latest('id')
                ->select('title', 'description', 'due_date', 'criteria', 'subject_id', 'teacher_id', 'class_id', 'semester_id')
                ->with(['subject', 'teacher', 'class', 'semester'])
                ->paginate(10);

            if ($assignments->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new AssignmentCollection($assignments),
                'Lấy tất cả thông tin bài tập thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(AssignmentRequest $request)
    {
        try {
            $data = $request->validated();
            $assignment = $this->assignmentService->createNewAssignment($data);

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Tạo mới thành công',
                Response::HTTP_CREATED);
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        try {
            $assignment = Assignment::where('id', $id)
                ->select('title', 'description', 'due_date', 'criteria', 'subject_id', 'teacher_id', 'class_id', 'semester_id')
                ->with(['subject', 'teacher', 'class', 'semester'])
                ->first();
            if ($assignment == null) {
                return $this->errorResponse('Bài tập không tồn tại hoặc bị xóa mất', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Lấy bài Assignment thành công',
                Response::HTTP_OK
            );
        }

        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(AssignmentRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $assignment = $this->assignmentService->updateAssignment($id, $data);
            return $this->successResponse(
                new AssignmentResource($assignment),
                'Đổi file Assignment thành công',
                Response::HTTP_OK
            );
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $assignment = $this->assignmentService->deleteAssignment($id);

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Xóa file thành công',
                Response::HTTP_NO_CONTENT
            );
        }

        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $assignments = Assignment::latest('id')
                ->select('title', 'description', 'due_date', 'criteria', 'subject_id', 'teacher_id', 'class_id', 'semester_id')
                ->with(['subject', 'teacher', 'class', 'semester'])
                ->onlyTrashed()
                ->paginate(10);

            if ($assignments->isEmpty()) {
                return $this->errorResponse(
                    'Khoong có dữ liệu',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                new AssignmentCollection($assignments),
                'Lấy tất cả thông tin bài tập đã xóa thành công',
                Response::HTTP_OK
            );
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($id)
    {
        try {
            $assignment = $this->assignmentService->restoreAssignment($id);

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Khôi phục bài tập thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id)
    {
        try {
            $this->assignmentService->forceDeleteAssignment($id);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn bài tập thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
