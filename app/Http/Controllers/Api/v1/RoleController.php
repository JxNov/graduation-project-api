<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponseTrait;
use App\Services\RoleService;

class RoleController extends Controller
{
    use ApiResponseTrait;

    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getRoles();

            if ($roles->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse($roles, 'Lấy dữ liệu thành công');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            if ($request->permissions) {
                $request->validate([
                    'permissions' => 'required'
                ]);
            }

            $role = $this->roleService->createRoleAndAttachPermissions($request->input('name'), $request->input('permissions'));

            return $this->successResponse($role, 'Tạo role và gán quyền thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function update(Request $request, $slug): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            if ($request->permissions) {
                $request->validate([
                    'permissions' => 'required'
                ]);
            }

            $role = $this->roleService->updateRoleAndSyncPermissions($slug, $request->input('name'), $request->input('permissions'));

            return $this->successResponse($role, 'Cập nhật role và gán quyền thành công');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($slug): JsonResponse
    {
        try {
            $this->roleService->deleteRole($slug);

            return $this->successResponse([], 'Xóa role thành công');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function restore($name): JsonResponse
    {
        try {
            $role = $this->roleService->restoreRole($name);

            return $this->successResponse([$role], 'Khôi phục role thành công');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function forceDelete($slug): JsonResponse
    {
        try {
            $this->roleService->forceDeleteRole($slug);

            return $this->successResponse([], 'Xóa vĩnh viễn role thành công');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
