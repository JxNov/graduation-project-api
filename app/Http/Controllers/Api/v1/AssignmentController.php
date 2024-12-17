<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Http\Resources\AssignmentCollection;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Classes;
use App\Services\AssignmentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    use ApiResponseTrait;

    protected $assignmentService;
    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index($classSlug)
    {
        try {
            $class = Classes::where('slug', $classSlug)->first();

            // Kiểm tra nếu lớp không tồn tại
            if (!$class) {
                return $this->errorResponse('Lớp không tồn tại', Response::HTTP_NOT_FOUND);
            }

            // Lấy danh sách assignments thuộc lớp đó
            $assignments = Assignment::where('class_id', $class->id)
                ->latest('id')
                ->with(['subject', 'teacher', 'class', 'semester'])
                ->get();

            //Kiểm tra các assignment đã có bao nhiêu học sinh nộp
            $assignments = $assignments->map(function ($assignment) {
                $assignment->submitted = $assignment->submittedAssignments()->count();
                $assignment->not_submitted = $assignment->class->students()->count() - $assignment->submitted;
                return $assignment;
            });

            // Kiểm tra nếu không có dữ liệu
            if ($assignments->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            // Trả về dữ liệu assignments
            return $this->successResponse(
                new AssignmentCollection($assignments),
                'Lấy danh sách bài tập thành công',
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

    public function show($classSlug, $assignmentSlug)
    {
        try {
            // Tìm lớp học dựa trên classSlug
            $class = Classes::where('slug', $classSlug)->first();

            if (!$class) {
                return $this->errorResponse('Lớp không tồn tại', Response::HTTP_NOT_FOUND);
            }

            // Tìm bài tập dựa trên assignmentSlug và class_id
            $assignment = Assignment::where('slug', $assignmentSlug)
                ->where('class_id', $class->id)
                ->with(['subject', 'teacher', 'class', 'semester'])
                ->first();

            //Kiểm tra assignment này đã có bao nhiêu học sinh nộp
            $submittedCount = $assignment->submittedAssignments()->count();

            // Tổng số học sinh trong lớp
            $totalStudents = $class->students()->count();

            // Tính số học sinh chưa nộp
            $notSubmittedCount = $totalStudents - $submittedCount;

            $assignment->submitted = $submittedCount;
            $assignment->not_submitted = $notSubmittedCount;

            if (!$assignment) {
                return $this->errorResponse('Bài tập không tồn tại hoặc không thuộc lớp này', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Lấy bài Assignment thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }



    public function update(AssignmentRequest $request, $assignmentSlug)
    {
        try {
            $data = $request->validated();
            $assignment = $this->assignmentService->updateAssignment($assignmentSlug, $data);
            return $this->successResponse(
                new AssignmentResource($assignment),
                'Cập nhật Assignment thành công',
                Response::HTTP_OK
            );
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($assignmentSlug)
    {
        try {
            $this->assignmentService->deleteAssignment($assignmentSlug);

            return $this->successResponse(
                'Xóa bài tập thành công',
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
                ->get();

            if ($assignments->isEmpty()) {
                return $this->errorResponse(
                    'Không có dữ liệu',
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

    public function restore($assignmentSlug)
    {
        try {
            $assignment = $this->assignmentService->restoreAssignment($assignmentSlug);

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Khôi phục bài tập thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($assignmentSlug)
    {
        try {
            $this->assignmentService->forceDeleteAssignment($assignmentSlug);

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
