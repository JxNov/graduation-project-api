<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearClassRequest;
use App\Http\Resources\AcademicYearClassCollection;
use App\Http\Resources\AcademicYearClassResource;
use App\Models\AcademicYearClass;
use App\Services\AcademicYearClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class AcademicYearClassController extends Controller
{
    use ApiResponseTrait;

    protected $academicYearClassService;

    public function __construct(AcademicYearClassService $academicYearClassService)
    {
        $this->academicYearClassService = $academicYearClassService;
    }

    public function index()
    {
        try {
            $academicYearClasses = AcademicYearClass::select('id', 'academic_year_id', 'class_id')
                ->latest('id')
                ->paginate(10);

            if ($academicYearClasses->isEmpty()) {
                return $this->errorResponse(
                    'Không có dữ liệu',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                new AcademicYearClassCollection($academicYearClasses),
                'Lấy danh sách lớp học của các năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(AcademicYearClassRequest $request)
    {
        try {
            $data = $request->validated();

            $academicYearClass = $this->academicYearClassService->createNewAcademicYearClass($data);

            return $this->successResponse(
                new AcademicYearClassResource($academicYearClass),
                'Thêm lớp học vào năm học thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        try {
            $academicYearClass = AcademicYearClass::select('id', 'academic_year_id', 'class_id')
                ->where('id', $id)
                ->first();

            if ($academicYearClass === null) {
                return $this->errorResponse(
                    'Không có dữ liệu',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                new AcademicYearClassResource($academicYearClass),
                'Lấy lớp học của năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(AcademicYearClassRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $academicYearClass = $this->academicYearClassService->updateAcademicYearClass($data, $id);

            return $this->successResponse(
                new AcademicYearClassResource($academicYearClass),
                'Cập nhật lớp học vào năm học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $this->academicYearClassService->deleteAcademicYearClass($id);

            return $this->successResponse(
                null,
                'Xóa lớp học của năm học thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $academicYearClasses = AcademicYearClass::select('id', 'academic_year_id', 'class_id')
                ->latest('id')
                ->onlyTrashed()
                ->paginate(10);

            if ($academicYearClasses->isEmpty()) {
                return $this->errorResponse(
                    'Không có dữ liệu',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                new AcademicYearClassCollection($academicYearClasses),
                'Lấy danh sách lớp học đã xóa của các năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($id)
    {
        try {
            $academicYearClass = $this->academicYearClassService->restoreAcademicYearClass($id);

            return $this->successResponse(
                new AcademicYearClassResource($academicYearClass),
                'Khôi phục lớp học của năm học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id){
        try {
            $this->academicYearClassService->forceDeleteAcademicYearClass($id);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn lớp học của năm học thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
