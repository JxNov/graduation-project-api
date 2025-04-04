<?php

namespace App\Services;

use App\Events\AttendanceSaved;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Classes;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function saveAttendance($data)
    {
        return DB::transaction(function () use ($data) {
            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $dayNow = Carbon::now()->toDateString();
            $attendance = Attendance::where('class_id', $class->id)->select('date')->first();

            if ($attendance && Carbon::parse($attendance->date)->toDateString() === $dayNow) {
                throw new Exception('Đã điểm danh lớp này rồi');
            }

            $attendance = Attendance::create([
                'date' => now(),
                'shifts' => $this->determineShift(),
                'class_id' => $class->id
            ]);

            foreach ($class->students as $student) {
                $studentData = collect($data['students'])
                    ->where('username', $student->username)
                    ->first();

                if ($studentData) {
                    $attendance->attendanceDetails()->create([
                        'student_id' => $student->id,
                        'status' => $studentData['status'],
                        'reason' => $studentData['reason'] ?? null,
                    ]);
                } else {
                    $attendance->attendanceDetails()->create([
                        'student_id' => $student->id,
                        'status' => 'Absent',
                        'reason' => null,
                    ]);
                }
            }

            event(new AttendanceSaved($attendance));
            return $attendance;
        });
    }

    public function updateAttendance($data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $attendance = Attendance::find($id);

            if ($attendance === null) {
                throw new Exception('Không tìm thấy kết quả điểm danh');
            }

            $dayNow = Carbon::now()->toDateString();

            if (Carbon::parse($attendance->date)->toDateString() < $dayNow) {
                throw new Exception('Đã quá hạn để cập nhật điểm danh');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            if (!isset($data['students']) || !is_array($data['students'])) {
                throw new Exception('Danh sách học sinh không hợp lệ.');
            }

            $attendance->update([
                'date' => now(),
                'shifts' => $this->determineShift(),
                'class_id' => $class->id
            ]);

            foreach ($class->students as $student) {
                $studentData = collect($data['students'])
                    ->where('username', $student->username)
                    ->first();

                $attendanceDetail = $attendance->attendanceDetails()
                    ->where('student_id', $student->id)
                    ->first();

                $status = $studentData['status'] ?? 'Absent';
                $reason = $studentData['reason'] ?? null;

                if ($attendanceDetail) {
                    $attendanceDetail->update([
                        'status' => $status,
                        'reason' => $reason,
                    ]);
                } else {
                    $attendance->attendanceDetails()->updateOrCreate([
                        'student_id' => $student->id,
                    ], [
                        'status' => $status,
                        'reason' => $reason,
                    ]);
                }
            }

            event(new AttendanceSaved($attendance));
            return $attendance;
        });
    }


    public function updateStudentAttendance($data, $user, $attendance)
    {
        return DB::transaction(function () use ($data, $user, $attendance) {
            $attendanceDetail = AttendanceDetail::firstOrCreate([
                'attendance_id' => $attendance->id,
                'student_id' => $user->id,
                'status' => 'Present',
            ]);

            event(new AttendanceSaved($attendance));
            return $attendanceDetail;
        });
    }

    private function determineShift()
    {
        $currentHour = Carbon::now('Asia/Ho_Chi_Minh')->hour;
        return $currentHour < 12 ? 'Morning' : 'Afternoon';
    }
}
