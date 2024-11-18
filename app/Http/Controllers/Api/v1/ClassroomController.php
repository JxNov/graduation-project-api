<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    use ApiResponseTrait;

    public function getClassroomForTeacher()
    {
        try {
            $user = Auth::user();

            $classrooms = $user->teachingClasses()
                ->select('classes.name as className', 'classes.slug as classSlug')
                ->get();

            $data = $classrooms->map(function ($classroom) use ($user) {
                return [
                    'className' => $classroom->className,
                    'classSlug' => $classroom->classSlug,
                    'teacherName' => $user->name,
                    'teacherImage' => $user->image,
                ];
            });
            return $this->successResponse(
                $data,
                'Lấy danh sách lớp học của giáo viên thành công',
                Response::HTTP_OK

            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getDetailClassroomForTeacher($slug)
    {
        try {
            $user = Auth::user();

            $class = Classes::where('slug', $slug)
                ->with([
                    'classTeachers' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select('users.id', 'users.name', 'users.username');
                    },
                    'subjects' => function ($query) {
                        $query->select('subjects.id', 'subjects.name', 'subjects.slug', 'subjects.description');
                    },
                    'assignments' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select(
                                'assignments.id',
                                'assignments.title',
                                'assignments.slug',
                                'assignments.description',
                                'assignments.due_date',
                                'assignments.criteria',
                                'assignments.weight',
                                'assignments.class_id',
                                'assignments.teacher_id',
                                'assignments.subject_id'
                            );
                    }
                ])
                ->first();

            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $assignmentsGroup = $class->assignments->groupBy('subject_id')->map(function ($assignments) {
                return $assignments->map(function ($assignment) {
                    return [
                        'title' => $assignment->title,
                        'slug' => $assignment->slug,
                        'description' => $assignment->description,
                        'due_date' => $assignment->due_date,
                        'criteria' => $assignment->criteria,
                        'weight' => $assignment->weight,
                    ];
                });
            });

            // dung` get->subject_id vi` o tren da nhom' theo subject_id
            $subjects = $class->subjects->map(function ($subject) use ($assignmentsGroup) {
                $assignmentsForSubject = $assignmentsGroup->get($subject->id, []);

                return [
                    'name' => $subject->name,
                    'slug' => $subject->slug,
                    'description' => $subject->description,
                    'assignments' => $assignmentsForSubject,  // Gán bài tập vào môn học
                ];
            });

            $data = [
                'className' => $class->name,
                'classSlug' => $class->slug,
                'classCode' => $class->code,
                'subjects' => $subjects,
            ];

            return $this->successResponse(
                $data,
                'Thông tin lớp học lấy thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getAssignmentClassroom($slug)
    {
        try {
            $user = Auth::user();

            $class = Classes::where('slug', $slug)
                ->with([
                    'classTeachers' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select('users.id', 'users.name', 'users.username');
                    },
                    'subjects' => function ($query) {
                        $query->select('subjects.id', 'subjects.name', 'subjects.slug', 'subjects.description');
                    },
                    'assignments' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select(
                                'assignments.id',
                                'assignments.title',
                                'assignments.slug',
                                'assignments.description',
                                'assignments.due_date',
                                'assignments.criteria',
                                'assignments.weight',
                                'assignments.class_id',
                                'assignments.teacher_id',
                                'assignments.subject_id'
                            );
                    }
                ])
                ->first();

            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $assignmentsGroup = $class->assignments->groupBy('subject_id')->map(function ($assignments) {
                return $assignments->map(function ($assignment) {
                    return [
                        'title' => $assignment->title,
                        'slug' => $assignment->slug,
                        'description' => $assignment->description,
                        'due_date' => $assignment->due_date,
                        'criteria' => $assignment->criteria,
                        'weight' => $assignment->weight,
                    ];
                });
            });

            // dung` get->subject_id vi` o tren da nhom' theo subject_id
            $subjects = $class->subjects->map(function ($subject) use ($assignmentsGroup) {
                $assignmentsForSubject = $assignmentsGroup->get($subject->id, []);

                return [
                    'name' => $subject->name,
                    'slug' => $subject->slug,
                    'description' => $subject->description,
                    'assignments' => $assignmentsForSubject,  // Gán bài tập vào môn học
                ];
            });

            return $this->successResponse(
                $subjects,
                'Lấy bài tập thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getStudentClassroom($slug)
    {
        try {
            $user = Auth::user();

            $class = Classes::where('slug', $slug)
                ->with([
                    'students',
                    'classTeachers' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id);
                    }
                ])
                ->first();

            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $students = $class->students->map(function ($student) {
                return [
                    'name' => $student->name,
                    'image' => $student->image,
                ];
            });

            $data = [
                'teacherName' => $user->name,
                'teacherImage' => $user->image,
                'students' => $students,
                'numberOfStudents' => $students->count()
            ];

            return $this->successResponse(
                $data,
                'Lấy danh sách học sinh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
