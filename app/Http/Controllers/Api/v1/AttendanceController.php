<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Http\Resources\AttendanceCollection;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Classes;
use App\Services\AttendanceService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
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

    public function index()
    {
        try {
            $attendances = Attendance::latest('id')
                ->select('id', 'date', 'shifts', 'class_id')
                ->with(['class.teacher', 'class.students', 'attendanceDetails'])
                ->paginate(10);

            if ($attendances->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            $data = $attendances->map(function ($attendance) {
                $totalStudents = $attendance->class->students->count();

                // số hs đã điểm danh
                $attendedStudents = $attendance->attendanceDetails
                    ->where('status', '!=', AttendanceDetail::_STATUS['Absent'])
                    ->count();

                return [
                    'id' => $attendance->id,
                    'date' => Carbon::parse($attendance->date)->format('d/m/Y'),
                    'shifts' => $attendance->shifts,
                    'className' => $attendance->class->name,
                    'teacherName' => $attendance->class->teacher->name,
                    'totalStudents' => $totalStudents,
                    'attendedStudents' => $attendedStudents
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy tất cả kết quả điểm danh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function studentInClass(Request $request)
    {
        try {
            $class = Classes::where('slug', $request->classSlug)->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $students = $class->students;
            $teacherName = $class->teacher->name;
            $className = $class->name;
            $numberStudentInClass = $students->count();

            $result = $students->map(function ($student) {
                return [
                    'name' => $student->name,
                    'username' => $student->username,
                    'email' => $student->email,
                ];
            });

            return $this->successResponse(
                [
                    'className' => $className,
                    'teacherName' => $teacherName,
                    'numberStudentInClass' => $numberStudentInClass,
                    'students' => $result
                ],
                'Danh sách học sinh của lớp: ' . $className,
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
