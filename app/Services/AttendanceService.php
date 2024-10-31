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

            foreach ($data['students'] as $studentData) {
                $student = User::where('username', $studentData['username'])->first();

                if ($student) {
                    $attendance->attendanceDetails()->create([
                        'student_id' => $student->id,
                        'status' => $studentData['status'],
                        'reason' => $studentData['reason'] ?? null,
                    ]);
                }
            }

            return $attendance;
        });
    }
}