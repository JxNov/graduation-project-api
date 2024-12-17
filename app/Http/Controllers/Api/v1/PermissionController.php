<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;

class PermissionController extends Controller
{
    use ApiResponseTrait;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(): JsonResponse
    {
        try {
            $permissions = Permission::select('value', 'slug')->get();

            if ($permissions->isEmpty()) {
                throw new \Exception('Không có quyền nào.', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse($permissions, 'Danh sách quyền được lấy thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'value' => 'required'
            ]);

            $permission = $this->permissionService->createPermission($request->input('value'));

            return $this->successResponse($permission, 'Tạo quyền thành công.', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($slug): JsonResponse
    {
        try {
            $this->permissionService->deletePermission($slug);

            return $this->successResponse([], 'Xóa quyền thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
