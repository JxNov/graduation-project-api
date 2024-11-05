<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\User;
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

            $attendance = Attendance::create([
                'date' => now(),
                'shifts' => $data['shifts'],
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
                        'reason' => 'Không có mặt',
                    ]);
                }
            }

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

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $attendance->update([
                'date' => $attendance->date,
                'shifts' => $data['shifts'],
                'class_id' => $class->id
            ]);

            foreach ($class->students as $student) {
                $studentData = collect($data['students'])
                    ->where('username', $student->username)
                    ->first();

                $attendanceDetail = $attendance->attendanceDetails()
                    ->where('student_id', $student->id)
                    ->first();

                if ($studentData && $attendanceDetail) {
                    $attendanceDetail->update([
                        'status' => $studentData['status'],
                        'reason' => $studentData['reason'] ?? null,
                    ]);
                }
            }

            return $attendance;
        });
    }
}