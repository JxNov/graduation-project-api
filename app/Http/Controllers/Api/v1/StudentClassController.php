<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentClassRequest;
use App\Http\Resources\StudentClassCollection;
use App\Http\Resources\StudentClassResource;
use App\Models\Classes;
use App\Services\StudentClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentClassController extends Controller
{
    use ApiResponseTrait;
    protected $studentClassService;

    public function __construct(StudentClassService $studentClassService)
    {
        $this->studentClassService = $studentClassService;
    }

    public function importStudent(StudentClassRequest $request)
    {
        try {
            $data = $request->all();

            $this->studentClassService->importStudents($data);

            return $this->successResponse(
                [],
                'Danh sách học sinh đang được nhập, hãy đợi chút',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function index()
    {
        // Lấy lớp học với thông tin cần thiết, đã eager load học sinh
        $classes = Classes::select('id', 'name', 'slug', 'code', 'teacher_id')
            ->latest('id')
            ->with('students') // Lấy thông tin học sinh
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }
        return $this->successResponse(
            new StudentClassCollection($classes),
            'Lấy tất cả thông tin lớp học và học sinh thành công',
            Response::HTTP_OK
        );
    }
    public function store(Request $request)
    {
        $data = [
            'classSlug' => $request->classSlug,
            'username' => $request->username,
        ];

        try {
            $subject = $this->studentClassService->store($data);
            return $this->successResponse($subject, 'SUCCESS', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update(Request $request, $id)
    {
        $data = [
            'classSlug' => $request->classSlug,
        ];

        try {
            $subject = $this->studentClassService->update($data, $id);
            return $this->successResponse($subject, 'SUCCESS', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id)
    {
        try {
            $this->studentClassService->destroy($id);
            return $this->successResponse(null, "Xóa mềm thành công!", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($id)
    {
        try {
            $student = $this->studentClassService->backup($id);
            return $this->successResponse($student, "Khôi phục thành công", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function trash()
    {
        $classes = Classes::with(['students' => function ($query) {
            $query->withPivot('class_id', 'student_id')
                ->wherePivot('deleted_at', '!=', null);
        }])
            ->latest('id')
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new StudentClassCollection($classes),
            'Lấy tất cả thông tin lớp học và học sinh đã xoá thành công',
            Response::HTTP_OK
        );
    }


    public function forceDelete($id)
    {
        try {
            $this->studentClassService->forceDelete($id);
            return $this->successResponse(
                null,
                'Xóa vĩnh viễn thành công',
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function distributeStudents()
{
    try {
        $result = $this->studentClassService->distributeStudents();
        return $this->successResponse($result, 'Phân phối học sinh thành công!', Response::HTTP_OK);
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}
