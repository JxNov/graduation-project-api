<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\User;
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

                    $attendedStudentsIds = $attendance->attendanceDetails->pluck('student_id')->toArray();
                    $attendedStudents = count($attendedStudentsIds);

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
            $teacherImage = $class->teacher->image;
            $className = $class->name;
            $numberStudentInClass = $students->count();

            $today = Carbon::now()->format('Y-m-d');
            $attendance = Attendance::where('class_id', $class->id)
                ->whereDate('date', $today)
                ->first();

            $result = $students->map(function ($student) use ($attendance) {
                $attendanceStatus = 'Absent';

                if ($attendance) {
                    $attendanceDetail = $attendance->attendanceDetails
                        ->where('student_id', $student->id)
                        ->first();

                    if ($attendanceDetail) {
                        $attendanceStatus = $attendanceDetail->status;
                        $reason = $attendanceDetail->reason ?? null;
                    }
                }

                return [
                    'name' => $student->name,
                    'username' => $student->username,
                    'userImage' => $student->image,
                    'status' => $attendanceStatus,
                    'reason' => $reason ?? null,
                ];
            });

            return $this->successResponse(
                [
                    'id' => $attendance->id ?? null,
                    'className' => $className,
                    'teacherName' => $teacherName,
                    'teacherImage' => $teacherImage,
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

            $attendance = $this->attendanceService->saveAttendance($data);

            return response()->json(
                [
                    'id' => $attendance->id ?? null,
                    'message' => 'Đã lưu kết quả điểm danh ngày: ' . now()->format('d/m/Y'),
                ],
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
                    'reason' => $attendance->reason ?? null
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

    public function updateStudentAttendance(Request $request, $username)
    {
        $request->validate([
            'shifts' => 'required|string',
        ]);

        try {
            $user = User::with('classes')->where('username', $username)->first();
            if ($user === null) {
                throw new Exception('Người dùng không tồn tại hoặc đã bị xóa');
            }

            $attendance = Attendance::where('date', now()->format('Y-m-d'))
                ->where('shifts', $request->shifts)
                ->where('class_id', $user->classes->first()->id)
                ->first();

            if ($attendance === null) {
                throw new Exception('Không tìm thấy thông tin điểm danh');
            }

            $data = $request->all();

            $result = $this->attendanceService->updateStudentAttendance($data, $user, $attendance);

            return response()->json([
                'className' => $attendance->class->name,
                'username' => $user->username,
                'date' => $attendance->date,
                'shifts' => $attendance->shifts,
                'status' => $result['status'] ?? 'Absent',
                'reason' => $result['reason'] ?? null
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
