<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ClassResource;
use App\Models\Article;
use App\Models\Classes;
use App\Services\ClassroomService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    use ApiResponseTrait;

    protected $classroomService;

    public function __construct(ClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    public function getClassroomForTeacher()
    {
        try {
            $user = Auth::user();

            $classrooms = $user->teachingClasses()
                ->select('classes.name as className', 'classes.slug as classSlug', 'classes.teacher_id')
                ->get();
            // \Illuminate\Support\Facades\Log::info($classrooms);
            $data = $classrooms->map(function ($classroom) {
                return [
                    'className' => $classroom->className,
                    'classSlug' => $classroom->classSlug,
                    'teacherName' => $classroom->teacher->name,
                    'teacherImage' => $classroom->teacher->image ?? null,
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

    public function getClassroomForStudent()
    {
        try {
            $user = Auth::user();

            $classrooms = $user->classes()
                ->select('classes.name as className', 'classes.slug as classSlug', 'classes.teacher_id')
                ->get();
            $data = $classrooms->map(function ($classroom) use ($user) {
                return [
                    'className' => $classroom->className,
                    'classSlug' => $classroom->classSlug,
                    'teacherName' => $classroom->teacher->name,
                    'teacherImage' => $classroom->teacher->image ?? null,
                ];
            });
            return $this->successResponse(
                $data,
                'Lấy danh sách lớp học của học sinh thành công',
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
                    'assignments' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select(
                                'assignments.id',
                                'assignments.title',
                                'assignments.slug',
                                'assignments.description',
                                'assignments.due_date',
                                'assignments.criteria',
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

            $articles = Article::where('class_id', $class->id)
                ->select('id', 'content', 'teacher_id', 'created_at')
                ->with(['teacher', 'comments'])
                ->get();

            $assignments = $class->assignments->map(function ($assignment) {
                return [
                    'title' => $assignment->title,
                    'slug' => $assignment->slug,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'criteria' => $assignment->criteria,
                ];
            });

            $articleData = $articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'content' => $article->content,
                    'teacherName' => $article->teacher->name,
                    'teacherImage' => $article->teacher->image,
                    'createdAt' => Carbon::parse($article->created_at),
                    'comments' => $article->comments->map(function ($comment) {
                        return [
                            'content' => $comment->content,
                            'createdAt' => Carbon::parse($comment->created_at),
                            'id' => $comment->id,
                            'name' => $comment->user->name,
                            'username' => $comment->user->username,
                            'userImage' => $comment->user->image,
                        ];
                    })
                ];
            });

            $data = [
                'className' => $class->name,
                'classSlug' => $class->slug,
                'classCode' => $class->code,
                'assignments' => $assignments,
                'articles' => $articleData
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
                    'assignments' => function ($query) use ($user) {
                        $query->where('teacher_id', $user->id)
                            ->select(
                                'assignments.id',
                                'assignments.title',
                                'assignments.slug',
                                'assignments.description',
                                'assignments.due_date',
                                'assignments.criteria',
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

            $assignments = $class->assignments->map(function ($assignment) {
                return [
                    'title' => $assignment->title,
                    'slug' => $assignment->slug,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'criteria' => $assignment->criteria,
                ];
            });

            return $this->successResponse(
                $assignments,
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
            $class = Classes::where('slug', $slug)
                ->with([
                    'students',
                    'classTeachers'
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

            $teachers = $class->classTeachers->map(function ($teacher) {
                return [
                    'name' => $teacher->name,
                    'image' => $teacher->image,
                ];
            });

            $data = [
                'teachers' => $teachers,
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

    public function getClassMaterialClassroom($slug)
    {
        try {
            $class = Classes::where('slug', $slug)
                ->with([
                    'subjects',
                    'materials'
                ])
                ->first();

            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $materials = $class->materials->map(function ($material) {
                return [
                    'title' => $material->title,
                    'slug' => $material->slug,
                    'description' => $material->description,
                    'filePath' => $material->file_path,
                    'subjectName' => $material->subject->name,
                    'subjectSlug' => $material->subject->slug
                ];
            });

            return $this->successResponse(
                $materials,
                'Lấy danh sách tài liệu của lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function joinClassroomByCode(Request $request)
    {
        try {
            $code = $request->code;
            $class = $this->classroomService->joinClassroomByCode($code);

            return $this->successResponse(
                new ClassResource($class),
                'Tham gia lớp học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
