<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\TeacherResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class UserController extends Controller
{
    use ApiResponseTrait;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->index();

            return $this->successResponse($users, 'Lấy danh sách người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function getUserRoles($username): JsonResponse
    {
        try {
            $user = $this->userService->getUserRoles($username);

            return $this->successResponse($user, 'Lấy thông tin vai trò của người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function assignRoles(Request $request, $username): JsonResponse
    {
        if ($request->roles_slug) {
            $request->validate([
                'roles_slug' => 'required|exists:roles,slug',
            ]);
        }

        try {
            $user = $this->userService->assignRoles($username, $request->input('roles_slug'));

            return $this->successResponse($user, 'Gán quyền cho người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function revokeRoles($username): JsonResponse
    {
        try {
            $user = $this->userService->revokeRoles($username);

            return $this->successResponse($user, 'Thu hồi quyền của người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function getUserPermissions($username): JsonResponse
    {
        try {
            $user = $this->userService->getUserPermissions($username);

            return $this->successResponse($user, 'Lấy thông tin quyền của người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function assignPermissions(Request $request, $username): JsonResponse
    {
        if ($request->permissions_slug) {
            $request->validate([
                'permissions_slug' => 'required|exists:permissions,slug',
            ]);
        }

        try {
            $user = $this->userService->assignPermissions($username, $request->input('permissions_slug'));

            return $this->successResponse($user, 'Gán quyền cho người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function revokePermissions($username): JsonResponse
    {
        try {
            $user = $this->userService->revokePermissions($username);

            return $this->successResponse($user, 'Thu hồi quyền của người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function assignRolesAndPermissions(Request $request): JsonResponse
    {
        if ($request->username) {
            $request->validate([
                'users' => 'required|exists:users,email',
            ]);
        }

        if ($request->roles) {
            $request->validate([
                'roles' => 'required|exists:roles,slug',
            ]);
        }

        if ($request->permissions) {
            $request->validate([
                'permissions' => 'required|exists:permissions,value',
            ]);
        }

        try {
            $user = $this->userService->assignRolesAndPermissions($request->input('users'), $request->input('roles'), $request->input('permissions'));

            return $this->successResponse($user, 'Gán quyền và quyền cho người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function revokeRolesAndPermissions(Request $request): JsonResponse
    {
        if ($request->username) {
            $request->validate([
                'username' => 'required|exists:users,username',
            ]);
        }

        try {
            $user = $this->userService->revokeRolesAndPermissions($request->input('username'));

            return $this->successResponse($user, 'Thu hồi quyền và quyền của người dùng thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function showStudent($username){
        $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();
        $student = User::whereHas('roles', function ($query) use ($roleStudent) {
            $query->where('role_id', $roleStudent->id);
        })
        ->where('username',$username)
            ->first();
        return new StudentResource($student);
    }
    public function updateStudent(Request $request,$username ){
        try {
            $data =[
                'image'=>$request->image,
                'password'=>$request->password
            ] ;
            $user = $this->userService->updateStudent($data, $username);
            return $this->successResponse(new StudentResource($user), 'Thay đổi thông tin học sinh thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function showTeacher($username){
        $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();
        $teacher = User::whereHas('roles', function ($query) use ($roleTeacher) {
            $query->where('role_id', $roleTeacher->id);
        })
        ->where('username',$username)
            ->first();
        return new TeacherResource($teacher);
    }
    public function updateTeacher(Request $request,$username ){
        try {
            $data =[
                'image'=>$request->image,
                'password'=>$request->password
            ] ;
            $user = $this->userService->updateTeacher($data, $username);
            return $this->successResponse(new TeacherResource($user), 'Thay đổi thông tin giáo viên thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
