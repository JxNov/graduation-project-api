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
                    'ClassSlug' => $classroom->classSlug,
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

    // public function getDetailClassroom($slug)
    // {
    //     try {
    //         $user = Auth::user();

    //         $class = Classes::where('slug', $slug)
    //             ->select('id', 'slug', 'name')
    //             ->first();
    //         // \Illuminate\Support\Facades\Log::info($class);

    //         if ($classroom === null) {
    //             throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
    //         }

    //         // $assignments = 
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }
}
