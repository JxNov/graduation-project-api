<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Classes;
use App\Services\AttendanceService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttendanceController extends Controller
{
    use ApiResponseTrait;

    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function studentInClass(Request $request)
    {
        try {
            $class = Classes::where('slug', $request->classSlug)->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $students = $class->students;
            $result = [];

            foreach ($students as $student) {
                $result[] = [
                    'name' => $student->name,
                    'username' => $student->username,
                    'email' => $student->email,
                ];
            }

            return $this->successResponse(
                $result,
                'Danh sách học sinh của lớp: ' . $class->name,
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function save(AttendanceRequest $request)
    {
        try {
            $data = $request->validated();

            $this->attendanceService->saveAttendance($data);

            return $this->successResponse(
                null,
                'Đã lưu kết quả điểm danh ngày: ' . now()->format('d/m/Y'),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(AttendanceRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $this->attendanceService->updateAttendance($data, $id);

            return $this->successResponse(
                null,
                'Đã cập nhật kết quả điểm danh ngày: ' . now()->format('d/m/Y'),
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
