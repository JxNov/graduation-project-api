<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockClassRequest;
use App\Http\Resources\BlockClassCollection;
use App\Http\Resources\BlockClassResource;
use App\Models\BlockClass;
use App\Services\BlockClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class BlockClassController extends Controller
{
    use ApiResponseTrait;

    protected $blockClassService;

    public function __construct(BlockClassService $blockClassService)
    {
        $this->blockClassService = $blockClassService;
    }

    public function index()
    {
        try {
            $blockClasses = BlockClass::select('id', 'block_id', 'class_id')
                ->latest('id')
                ->paginate(10);

            if ($blockClasses->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockClassCollection($blockClasses),
                'Lấy tất cả thông tin lớp học của khối học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(BlockClassRequest $request)
    {
        try {
            $data = $request->validated();
            $blockClass = $this->blockClassService->createNewBlockClass($data);
            return $this->successResponse(
                new BlockClassResource($blockClass),
                'Thêm mới lớp học vào khối thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        try {
            $blockClass = BlockClass::where('id', $id)->first();

            if ($blockClass === null) {
                return $this->errorResponse('Không tìm thấy lớp học của khối', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockClassResource($blockClass),
                'Lấy thông tin lớp học của khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(BlockClassRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $blockClass = $this->blockClassService->updateBlockClass($id, $data);

            return $this->successResponse(
                new BlockClassResource($blockClass),
                'Cập nhật lớp học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $this->blockClassService->deleteBlockClass($id);
            return $this->successResponse(
                null,
                'Xóa lớp học thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $blockClasses = BlockClass::select('id', 'block_id', 'class_id')
                ->latest('id')
                ->onlyTrashed()
                ->paginate(10);

            if ($blockClasses->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new BlockClassCollection($blockClasses),
                'Lấy tất cả thông tin lớp học đã xóa của khối học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($id)
    {
        try {
            $blockClass = $this->blockClassService->restoreBlockClass($id);
            return $this->successResponse(
                new BlockClassResource($blockClass),
                'Khôi phục lớp học của khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id)
    {
        try {
            $this->blockClassService->forceDeleteBlockClass($id);
            return $this->successResponse(
                null,
                'Xóa vĩnh viễn lớp học thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
