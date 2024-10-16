<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SemesterRequest;
use App\Http\Resources\SemesterCollection;
use App\Http\Resources\SemesterResource;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\SemesterService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class SemesterController extends Controller
{
    use ApiResponseTrait;

    protected $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
    }

    public function index()
    {
        $semesters = Semester::select('id', 'name', 'slug', 'start_date', 'end_date', 'academic_year_id')
            ->latest('id')
            ->paginate(6);

        if ($semesters->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new SemesterCollection($semesters),
            'Lấy tất cả thông tin kỳ học thành công',
            Response::HTTP_OK
        );
    }

    public function create()
    {
        $academicYears = AcademicYear::select('id', 'name')
            ->latest('id')
            ->get();

        if ($academicYears->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            $academicYears,
            'Lấy tất cả các năm học thành công',
            Response::HTTP_OK
        );
    }

    public function store(SemesterRequest $request)
    {
        try {
            $data = $request->validated();

            $semester = $this->semesterService->createNewSemester($data);

            return $this->successResponse(
                new SemesterResource($semester),
                'Đã thêm thành công kỳ học  mới',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function edit($slug)
    {
        $academicYears = AcademicYear::select('id', 'name')
            ->latest('id')
            ->get();

        if ($academicYears->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        $semester = Semester::where('slug', $slug)->first();

        if (!$semester) {
            return $this->errorResponse('Kỳ học không tồn tại', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            [
                new SemesterResource($semester),
                'academicYears' => $academicYears
            ],
            'Lấy thông tin kỳ học thành công',
            Response::HTTP_OK
        );
    }

    public function show($slug)
    {
        $semester = Semester::where('slug', $slug)->first();

        if (!$semester) {
            return $this->errorResponse('Kỳ học không tồn tại', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new SemesterResource($semester),
            'Lấy thông tin kỳ học thành công',
            Response::HTTP_OK
        );
    }

    public function update(SemesterRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $semester = $this->semesterService->updateSemester($data, $slug);

            return $this->successResponse(
                new SemesterResource($semester),
                'Đã cập nhật kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->semesterService->deleteSemester($slug);

            return $this->successResponse(
                null,
                'Đã xóa kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $semesters = Semester::onlyTrashed()
            ->select('id', 'name', 'slug', 'start_date', 'end_date', 'academic_year_id')
            ->latest('id')
            ->paginate(6);

        if ($semesters->isEmpty()) {
            return $this->successResponse(
                null,
                'Không có dữ liệu',
                Response::HTTP_OK
            );
        }

        return $this->successResponse(
            new SemesterCollection($semesters),
            'Lấy tất cả thông tin kỳ học đã xóa thành công',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $semester = $this->semesterService->restoreSemester($slug);

            return $this->successResponse(
                new SemesterResource($semester),
                'Đã khôi phục kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->semesterService->forceDeleteSemester($slug);

            return $this->successResponse(
                null,
                'Đã xóa vĩnh viễn kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
