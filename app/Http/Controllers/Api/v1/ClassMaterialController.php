<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassMaterialRequest;
use App\Http\Resources\ClassMaterialCollection;
use App\Http\Resources\ClassMaterialResource;
use App\Models\ClassMaterial;
use App\Services\ClassMaterialService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class ClassMaterialController extends Controller
{
    use ApiResponseTrait;

    protected $classMaterialService;

    public function __construct(ClassMaterialService $classMaterialService)
    {
        $this->classMaterialService = $classMaterialService;
    }

    public function index()
    {
        try {
            $classMaterials = ClassMaterial::latest('id')
                ->select('id', 'material_id', 'class_id')
                ->with(['material', 'class'])
                ->paginate(10);

            if ($classMaterials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new ClassMaterialCollection($classMaterials),
                'Lấy tất cả thông tin tài liệu của lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(ClassMaterialRequest $request)
    {
        try {
            $data = $request->validated();

            $classMaterial = $this->classMaterialService->createNewClassMaterial($data);

            return $this->successResponse(
                new ClassMaterialResource($classMaterial),
                'Thêm tài liệu vào lớp thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        try {
            $classMaterial = ClassMaterial::where('id', $id)
                ->select('id', 'material_id', 'class_id')
                ->with(['material', 'class'])
                ->first();

            if ($classMaterial === null) {
                return $this->errorResponse('Dữ liệu không tồn tại hoặc đã bị xóa', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new ClassMaterialResource($classMaterial),
                'Lấy thông tin tài liệu của lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(ClassMaterialRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $classMaterial = $this->classMaterialService->updateClassMaterial($data, $id);

            return $this->successResponse(
                new ClassMaterialResource($classMaterial),
                'Cập nhật tài liệu vào lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $this->classMaterialService->deleteClassMaterial($id);

            return $this->successResponse(
                null,
                'Xóa tài liệu lớp thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $classMaterials = ClassMaterial::latest('id')
                ->select('id', 'material_id', 'class_id')
                ->with(['material', 'class'])
                ->onlyTrashed()
                ->paginate(10);

            if ($classMaterials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new ClassMaterialCollection($classMaterials),
                'Lấy tất cả thông tin tài liệu đã xóa của lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($id)
    {
        try {
            $classMaterial = $this->classMaterialService->restoreClassMaterial($id);

            return $this->successResponse(
                new ClassMaterialResource($classMaterial),
                'Khôi phục tài liệu vào lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id)
    {
        try {
            $this->classMaterialService->forceDeleteClassMaterial($id);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn tài liệu lớp thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
