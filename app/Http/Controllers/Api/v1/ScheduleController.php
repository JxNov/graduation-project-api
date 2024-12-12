<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassPeriod;
use App\Models\Schedule;
use App\Models\Subject;
use App\Services\ScheduleService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ScheduleController extends Controller
{
    use ApiResponseTrait;

    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->scheduleService->generateTimetable();
        return response()->json($result);
    }

    public function show($classSlug)
    {
        try {
            $class = Classes::where('slug', $classSlug)->first();

            if ($class === null) {
                return $this->errorResponse('Lớp học không tồn tại hoặc đã bị xóa', Response::HTTP_BAD_REQUEST);
            }

            $schedules = Schedule::where('class_id', $class->id)->get();

            if ($schedules->isEmpty()) {
                return $this->errorResponse('Lịch của lớp học chưa được tạo', Response::HTTP_BAD_REQUEST);
            }

            // lịch học của lớp
            $scheduleData = [];
            foreach ($schedules as $schedule) {
                $classPeriod = ClassPeriod::find($schedule->class_period_id);
                $subject = Subject::find($schedule->subject_id);
                $teacher = $subject->teachers->first();

                $scheduleData[$schedule->days][] = [
                    'period' => $classPeriod->lesson,
                    'start_time' => $classPeriod->start_time,
                    'end_time' => $classPeriod->end_time,
                    'subject' => $subject->name,
                    'teacher' => $teacher ? $teacher->name : 'Unknown',
                ];
            }

            // danh sách môn hjoc và giáo viên để sửa lịc học
            $subjects = Subject::all();
            $subjectTeachers = $subjects->map(function ($subject) {
                return [
                    'subjectName' => $subject->name,
                    'subjectSlug' => $subject->slug,
                    'teachers' => $subject->teachers->map(function ($teacher) {
                        return [
                            'teacherName' => $teacher->name,
                            'teacherUsername' => $teacher->username,
                            'teacherImage' => $teacher->image,
                        ];
                    }),
                ];
            });

            return response()->json([
                'schedule' => $scheduleData,
                'subjectTeachers' => $subjectTeachers,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
