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

    public function showUser($username)
    {
        $student = User::where('username', $username)
            ->first();
        return new StudentResource($student);
    }

    public function updateUser(Request $request, $username)
    {
        try {
            // Xác thực dữ liệu đầu vào
            if ($request->input('current_password') && $request->input('new_password') && $request->input('confirm_new_password')) {
                $validatedData = $request->validate([
                    'current_password' => 'required|string|min:6',
                    'new_password' => 'required|string|min:6',
                    'confirm_new_password' => 'required|string|min:6|same:new_password',
                ], [
                    'current_password.required' => 'Trường mật khẩu hiện tại là bắt buộc.',
                    'current_password.string' => 'Mật khẩu hiện tại phải là chuỗi ký tự.',
                    'current_password.min' => 'Mật khẩu hiện tại phải có ít nhất :min ký tự.',

                    'new_password.required' => 'Trường mật khẩu mới là bắt buộc.',
                    'new_password.string' => 'Mật khẩu mới phải là chuỗi ký tự.',
                    'new_password.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',

                    'confirm_new_password.required' => 'Xác nhận mật khẩu mới là bắt buộc.',
                    'confirm_new_password.string' => 'Xác nhận mật khẩu mới phải là chuỗi ký tự.',
                    'confirm_new_password.same' => 'Xác nhận mật khẩu mới phải khớp với mật khẩu mới.',

                ]);
            }

            // Lấy dữ liệu đầu vào
            $data = [];

            if ($request->has('images')) {
                $data['images'] = $request->images;
            }

            if ($request->has('current_password')) {
                $data['current_password'] = $request->current_password;
            }

            if ($request->has('new_password')) {
                $data['new_password'] = $request->new_password;
            }

            if ($request->has('confirm_new_password')) {
                $data['confirm_new_password'] = $request->confirm_new_password;
            }

            // Lấy người dùng từ username
            $user = $this->userService->updateUser($data, $username);

            // Trả về phản hồi thành công
            return $this->successResponse(new StudentResource($user), 'Thay đổi thông tin người dùng thành công!', Response::HTTP_OK);
        } catch
        (Exception $e) {
            // Xử lý lỗi
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate(
                [
                    'name' => 'required|max:50',
                    'date_of_birth' => 'required',
                    'gender' => 'required',
                    'address' => 'required',
                    'phone_number' => 'required|min:10',
                ],
                [
                    'name.required' => 'Tên đang trống',
                    'name.max' => 'Tên quá dài',
                    'date_of_birth.required' => 'Ngày sinh đang trống',
                    'gender.required' => 'Giới tính đang trống',
                    'address.required' => 'Địa chỉ đang trống',
                    'phone_number.required' => 'Số điện thoại đang trống',
                ]
            );

            $newUser = $this->userService->createNewUser($data);

            return $this->successResponse(
                new StudentResource($newUser),
                'Tạo mới người dùng thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($username)
    {
        try {
            $this->userService->deleteUser($username);
            return $this->successResponse(null, 'Xóa người dùng thành công', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
