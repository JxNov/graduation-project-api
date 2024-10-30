<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockMaterialRequest;
use App\Http\Resources\BlockMaterialCollection;
use App\Http\Resources\BlockMaterialResource;
use App\Models\BlockMaterial;
use App\Services\BlockMaterialService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class BlockMaterialController extends Controller
{
    use ApiResponseTrait;

    protected $blockMaterialService;

    public function __construct(BlockMaterialService $blockMaterialService)
    {
        $this->blockMaterialService = $blockMaterialService;
    }

    public function index()
    {
        try {
            $blockMaterials = BlockMaterial::latest('id')
                ->select('id', 'material_id', 'block_id')
                ->with(['material', 'block'])
                ->paginate(10);

            if ($blockMaterials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockMaterialCollection($blockMaterials),
                'Lấy tất cả thông tin tài liệu của khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(BlockMaterialRequest $request)
    {
        try {
            $data = $request->validated();

            $blockMaterial = $this->blockMaterialService->createNewBlockMaterial($data);

            return $this->successResponse(
                new BlockMaterialResource($blockMaterial),
                'Thêm tài liệu vào khối thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        try {
            $blockMaterial = BlockMaterial::where('id', $id)
                ->select('id', 'material_id', 'block_id')
                ->with(['material', 'block'])
                ->first();

            if ($blockMaterial === null) {
                return $this->errorResponse('Dữ liệu không tồn tại hoặc đã bị xóa', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockMaterialResource($blockMaterial),
                'Lấy thông tin tài liệu của khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(BlockMaterialRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $blockMaterial = $this->blockMaterialService->updateBlockMaterial($data, $id);

            return $this->successResponse(
                new BlockMaterialResource($blockMaterial),
                'Cập nhật tài liệu vào khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $this->blockMaterialService->deleteBlockMaterial($id);

            return $this->successResponse(
                null,
                'Xóa tài liệu khối thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $blockMaterials = BlockMaterial::latest('id')
                ->select('id', 'material_id', 'block_id')
                ->with(['material', 'block'])
                ->onlyTrashed()
                ->paginate(10);

            if ($blockMaterials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockMaterialCollection($blockMaterials),
                'Lấy tất cả thông tin tài liệu đã xóa của khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($id)
    {
        try {
            $blockMaterial = $this->blockMaterialService->restoreBlockMaterial($id);

            return $this->successResponse(
                new BlockMaterialResource($blockMaterial),
                'Khôi phục tài liệu vào khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id)
    {
        try {
            $this->blockMaterialService->forceDeleteBlockMaterial($id);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn tài liệu khối thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
