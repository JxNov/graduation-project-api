<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassPeriod;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\ScheduleService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    use ApiResponseTrait;

    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function store($blockSlug)
    {

        $result = $this->scheduleService->generateSchedules($blockSlug);
        return response()->json($result);
    }
    public function update(Request $request, $classSlug)
    {
        // Lấy dữ liệu từ request
        $data = $request->validate([
            'subjectSlug' => 'required|string',
            'days' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'usernameTeacher'=>'required',
            'is_morning' => 'required|boolean',
        ]);

        try {
            $result = $this->scheduleService->updateScheduleClass($data, $classSlug);
            
            return $this->successResponse(
                $result,
                'Thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
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
                $teacher = User::find($schedule->teacher_id);

                $scheduleData[$schedule->days][] = [
                    'startTime' => $classPeriod->start_time,
                    'endTime' => $classPeriod->end_time,
                    'subject' => $subject->name,
                    'teacher' => $teacher->name,
                    'isMorning' => $schedule->is_morning,
                ];
            }

            $sortedScheduleData = $this->sortScheduleData($scheduleData);

            // danh sách môn học và giáo viên để sửa lịch học
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
                'schedule' => $sortedScheduleData,
                'subjectTeachers' => $subjectTeachers,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function scheduleOfStudent()
    {
        try {
            $user = Auth::user();
            $classes = $user->classes;

            $scheduleData = [];

            foreach ($classes as $class) {
                $schedules = Schedule::where('class_id', $class->id)->get();

                if ($schedules->isEmpty()) {
                    continue;
                }

                $classSchedule = [];
                foreach ($schedules as $schedule) {
                    $classPeriod = ClassPeriod::find($schedule->class_period_id);
                    $subject = Subject::find($schedule->subject_id);
                    $teacher = User::find($schedule->teacher_id);

                    $classSchedule[$schedule->days][] = [
                        'startTime' => $classPeriod->start_time,
                        'endTime' => $classPeriod->end_time,
                        'subject' => $subject->name,
                        'teacher' => $teacher->name,
                        'isMorning' => $schedule->is_morning,
                    ];
                }

                $scheduleData = $this->sortScheduleData($classSchedule);
            }

            return response()->json(
                [
                    'class' => $class->name,
                    'schedule' => $scheduleData
                ],
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function scheduleOfTeacher()
    {
        try {
            $teacher = Auth::user();

            $schedules = Schedule::where('teacher_id', $teacher->id)->get();

            if ($schedules->isEmpty()) {
                return $this->errorResponse('Giảng viên không có lịch giảng dạy', Response::HTTP_BAD_REQUEST);
            }

            $scheduleData = [];
            foreach ($schedules as $schedule) {
                $class = Classes::find($schedule->class_id);
                $classPeriod = ClassPeriod::find($schedule->class_period_id);
                $subject = Subject::find($schedule->subject_id);

                $scheduleData[$schedule->days][] = [
                    'class' => $class->name,
                    'subject' => $subject->name,
                    'startTime' => $classPeriod->start_time,
                    'endTime' => $classPeriod->end_time,
                    'isMorning' => $schedule->is_morning,
                ];
            }

            $sortedScheduleData = $this->sortScheduleData($scheduleData);

            return response()->json($sortedScheduleData, Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function sortScheduleData($scheduleData)
    {
        $daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $sortedScheduleData = [];

        foreach ($daysOrder as $day) {
            if (isset($scheduleData[$day])) {
                $sortedScheduleData[$day] = $scheduleData[$day];
            }
        }

        return $sortedScheduleData;
    }

}
