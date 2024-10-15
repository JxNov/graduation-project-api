<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Http\Resources\AcademicYearCollection;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Models\Generation;
use App\Services\AcademicYearService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class AcademicYearController extends Controller
{
    use ApiResponseTrait;
    protected $academicYearService;

    public function __construct(AcademicYearService $academicYearService)
    {
        $this->academicYearService = $academicYearService;
    }

    public function index()
    {
        $academicYears = AcademicYear::select('id', 'name', 'slug', 'start_date', 'end_date', 'generation_id')
            ->latest('id')
            ->with('generation')
            ->paginate(6);

        if ($academicYears->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new AcademicYearCollection($academicYears),
            'Lấy tất cả thông tin năm học thành công',
            Response::HTTP_OK
        );
    }

    public function create()
    {
        $generations = Generation::select('id', 'name')
            ->latest('id')
            ->get();

        if ($generations->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            $generations,
            'Lấy tất cả thông tin khóa học thành công',
            Response::HTTP_OK
        );
    }

    public function store(AcademicYearRequest $request)
    {
        try {
            $data = $request->validated();

            $academicYear = $this->academicYearService->createNewAcademicYear($data);

            return $this->successResponse(
                new AcademicYearResource($academicYear),
                'Đã thêm thành công năm học mới',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($slug)
    {
        $academicYear = AcademicYear::select('id', 'name', 'slug', 'start_date', 'end_date', 'generation_id')
            ->where('slug', $slug)
            ->first();

        if ($academicYear == null) {
            return $this->errorResponse('Không tìm thấy năm học', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new AcademicYearResource($academicYear),
            'Lấy thông tin chi tiết năm học thành công',
            Response::HTTP_OK
        );
    }

    public function edit($slug)
    {
        $generations = Generation::select('id', 'name')
            ->latest('id')
            ->get();

        if ($generations->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        $academicYear = AcademicYear::select('id', 'name', 'slug', 'start_date', 'end_date', 'generation_id')
            ->where('slug', $slug)
            ->first();

        if ($academicYear == null) {
            return $this->errorResponse('Không tìm thấy năm học', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            [
                new AcademicYearResource($academicYear),
                'generations' => $generations,
            ],
            'Lấy tất cả thông tin khóa học thành công',
            Response::HTTP_OK
        );
    }

    public function update(AcademicYearRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $academicYear = $this->academicYearService->updateAcademicYear($data, $slug);

            return $this->successResponse(
                new AcademicYearResource($academicYear),
                'Đã cập nhật năm học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->academicYearService->deleteAcademicYear($slug);

            return $this->successResponse(
                null,
                'Đã xóa năm học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $academicYears = AcademicYear::onlyTrashed()
            ->select('id', 'name', 'slug', 'start_date', 'end_date', 'generation_id')
            ->latest('id')
            ->with('generation')
            ->paginate(6);

        if ($academicYears->isEmpty()) {
            return $this->successResponse(
                null,
                'Không có dữ liệu',
                Response::HTTP_OK
            );
        }

        return $this->successResponse(
            new AcademicYearCollection($academicYears),
            'Lấy tất cả thông tin năm học đã xóa thành công',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $academicYear = $this->academicYearService->restoreAcademicYear($slug);

            return $this->successResponse(
                new AcademicYearResource($academicYear),
                'Đã khôi phục năm học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->academicYearService->forceDeleteAcademicYear($slug);

            return $this->successResponse(
                null,
                'Đã xóa năm học vĩnh viễn',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
