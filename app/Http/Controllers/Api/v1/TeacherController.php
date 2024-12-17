<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Role;
use App\Models\User;
use App\Services\TeacherService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TeacherController extends Controller
{
    use ApiResponseTrait;
    protected $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->teacherService = $teacherService;
    }
    public function index()
    {
        $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();
        $teacher = User::whereHas('roles', function ($query) use ($roleTeacher) {
            $query->where('role_id', $roleTeacher->id);
        })
        ->with('subjects')
        ->get();
        return TeacherResource::collection($teacher);
    }
    public function store(TeacherRequest $request)
    {
        try {
            // Gọi service để tạo giáo viên mới
            $teacher = $this->teacherService->createTeacher($request->all());

            return  $this->successResponse(new TeacherResource($teacher), 'Thêm giáo viên mới thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function update(TeacherRequest $request, $username)
    {
        try {
            $user = $this->teacherService->updateTeacher($request->all(), $username);
            return $this->successResponse(new TeacherResource($user), 'Sửa giáo viên thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function destroy($username)
    {
        try {
            $user = $this->teacherService->destroy($username);
            return $this->successResponse(null, 'Xoá giáo viên thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function restore($username)
    {
        try {
            $user = $this->teacherService->backup($username);
            return $this->successResponse(null, 'Khôi phục giáo viên thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($username)
    {
        $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();
        $teacher = User::whereHas('roles', function ($query) use ($roleTeacher) {
            $query->where('role_id', $roleTeacher->id);
        })
            ->where('username', $username)
            ->first();
        return new TeacherResource($teacher);
    }
}
