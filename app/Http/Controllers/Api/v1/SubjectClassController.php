<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectClassRequest;
use App\Http\Resources\SubjectClassCollection;
use App\Http\Resources\SubjectClassResource;
use App\Models\Classes;
use App\Models\SubjectClasses;
use App\Services\SubjectClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectClassController extends Controller
{
    use ApiResponseTrait;

    protected $subjectClassService;

    public function __construct(SubjectClassService $subjectClassService)
    {
        $this->subjectClassService = $subjectClassService;
    }

    public function index()
    {
        try {
            $subjectclass = SubjectClasses::with(['class', 'subjects']) // Tải mối quan hệ class và subjects
                ->select('id', 'subject_id', 'class_id')
                ->latest('id')
                ->paginate(10);

            if ($subjectclass->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new SubjectClassCollection($subjectclass),
                'Lấy tất cả thông tin lớp học của khối học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(SubjectClassRequest $request)
    {
        try {
            // Cập nhật validation để chấp nhận mảng `subject_ids`
            $validatedData = $request->validated();

            // Gọi phương thức store trong service
            $this->subjectClassService->store($validatedData);
            return $this->successResponse(null, 'Các môn học đã được thêm vào lớp thành công', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(SubjectClassRequest $request, $id)
    {
        try {
            // Lấy dữ liệu đã được xác thực
            $data = $request->validated();

            // Gọi phương thức update trong service
            $this->subjectClassService->update($data, $id);

            return $this->successResponse(null, 'Cập nhật môn học cho lớp thành công', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $subjectClass = $this->subjectClassService->destroy($id);
            return $this->successResponse($subjectClass, 'Xóa thành công', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function restore($id)
    {
        try {
            $subject = $this->subjectClassService->backup($id);
            return $this->successResponse($subject, "Khôi phục thành công", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function trash()
    {
        try {
            $subjectclass = SubjectClasses::with(['class', 'subjects']) // Tải mối quan hệ class và subjects
                ->select('id', 'subject_id', 'class_id')
                ->latest('id')
                ->onlyTrashed()
                ->paginate(10);

            if ($subjectclass->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new SubjectClassCollection($subjectclass),
                'Lấy tất cả thông tin lớp học đã xóa của khối học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function forceDelete($id)
    {
        try {
            $this->subjectClassService->forceDelete($id);
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
