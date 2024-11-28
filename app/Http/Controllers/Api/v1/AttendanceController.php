<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Classes;
use App\Services\AttendanceService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
            $user = Auth::user();

            $teacherClasses = $user->teachingClasses;

            $today = Carbon::now();

            $attendances = Attendance::whereIn('class_id', $teacherClasses->pluck('id'))
                ->whereDate('date', $today)
                ->latest('id')
                ->select('id', 'date', 'shifts', 'class_id')
                ->with(['class.teacher', 'class.students', 'attendanceDetails'])
                ->get();

            $data = $teacherClasses->map(function ($class) use ($attendances, $today) {
                $attendance = $attendances->firstWhere('class_id', $class->id);

                if ($attendance) {
                    $totalStudents = $class->students->count();
                    $attendedStudents = $attendance->attendanceDetails
                        ->where('status', '!=', AttendanceDetail::_STATUS['Absent'])
                        ->count();

                    return [
                        'id' => $attendance->id,
                        'date' => Carbon::parse($attendance->date)->format('d/m/Y'),
                        'shifts' => $attendance->shifts,
                        'className' => $class->name,
                        'classSlug' => $class->slug,
                        'teacherName' => $class->teacher->name,
                        'totalStudents' => $totalStudents,
                        'attendedStudents' => $attendedStudents,
                        'status' => true,
                    ];
                } else {
                    return [
                        'date' => $today->format('d/m/Y'),
                        'className' => $class->name,
                        'classSlug' => $class->slug,
                        'teacherName' => $class->teacher->name,
                        'totalStudents' => $class->students->count(),
                        'attendedStudents' => 0,
                        'status' => false
                    ];
                }
            });

            return $this->successResponse(
                $data,
                'Lấy thông tin các lớp và kết quả điểm danh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function studentInClass($classSlug)
    {
        try {
            $class = Classes::where('slug', $classSlug)->first();

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
                'Danh sách học sinh của: ' . $className,
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function save(Request $request)
    {
        try {
            $data = $request->all();

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

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

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

    public function attendanceOfStudent()
    {
        try {
            $user = Auth::user();

            $attendances = $user->attendanceDetails;

            $data = $attendances->map(function ($attendance) {
                return [
                    'date' => Carbon::parse($attendance->attendance->date)->format('d/m/Y'),
                    'shifts' => $attendance->attendance->shifts,
                    'className' => $attendance->attendance->class->name,
                    'status' => $attendance->status,
                    'reason' => $attendance->reason
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy thông tin điểm danh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
