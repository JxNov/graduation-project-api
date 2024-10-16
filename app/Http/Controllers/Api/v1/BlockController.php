<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockRequest;
use App\Http\Resources\BlockCollection;
use App\Http\Resources\BlockResource;
use App\Models\Block;
use App\Services\BlockService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class BlockController extends Controller
{
    use ApiResponseTrait;
    protected $blockService;

    public function __construct(BlockService $blockService)
    {
        $this->blockService = $blockService;
    }

    public function index()
    {
        $blocks = Block::select('id', 'name', 'slug')
            ->latest('id')
            ->paginate(6);

        if ($blocks->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new BlockCollection($blocks),
            'Lấy tất cả thông tin khối thành công',
            Response::HTTP_OK
        );
    }

    public function store(BlockRequest $request)
    {
        try {
            $data = $request->validated();

            $block = $this->blockService->createNewBlock($data);

            return $this->successResponse(
                new BlockResource($block),
                'Thêm khối mới thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($slug)
    {
        $block = Block::select('id', 'name', 'slug')
            ->where('slug', $slug)
            ->first();

        if ($block === null) {
            return $this->errorResponse('Không tìm thấy khối', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new BlockResource($block),
            'Lấy thông tin chi tiết khối thành công',
            Response::HTTP_OK
        );
    }

    public function update(BlockRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $block = $this->blockService->updateBlock($data, $slug);

            return $this->successResponse(
                new BlockResource($block),
                'Cập nhật khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->blockService->deleteBlock($slug);

            return $this->successResponse(
                null,
                'Xóa khối thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $blocks = Block::onlyTrashed()
            ->select('id', 'name', 'slug')
            ->latest('id')
            ->paginate(6);

        if ($blocks->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new BlockCollection($blocks),
            'Lấy tất cả thông tin khối đã xóa thành công',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $this->blockService->restoreBlock($slug);

            return $this->successResponse(
                null,
                'Khôi phục khối thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->blockService->forceDeleteBlock($slug);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn khối thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
