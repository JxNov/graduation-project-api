<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest;
use App\Http\Resources\MaterialCollection;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use App\Services\MaterialService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class MaterialController extends Controller
{
    use ApiResponseTrait;

    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index()
    {
        try {
            $materials = Material::latest('id')
                ->select('title', 'slug', 'description', 'file_path', 'subject_id', 'teacher_id')
                ->with(['subject', 'teacher'])
                ->paginate(10);

            if ($materials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new MaterialCollection($materials),
                'Lấy tất cả thông tin tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(MaterialRequest $request)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->createNewMaterial($data);

            return $this->successResponse(
                new MaterialResource($material),
                'Tạo mới tài liệu thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($slug)
    {
        try {
            $material = Material::where('slug', $slug)
                ->select('title', 'slug', 'description', 'file_path', 'subject_id', 'teacher_id')
                ->with(['subject', 'teacher'])
                ->first();

            if ($material === null) {
                return $this->errorResponse('Tài liệu không tồn tại hoặc đã bị xóa', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new MaterialResource($material),
                'Lấy thông tin tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(MaterialRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->updateMaterial($data, $slug);

            return $this->successResponse(
                new MaterialResource($material),
                'Cập nhật tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->materialService->deleteMaterial($slug);

            return $this->successResponse(
                null,
                'Xóa tài liệu thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $materials = Material::latest('id')
                ->select('title', 'slug', 'description', 'file_path', 'subject_id', 'teacher_id')
                ->with(['subject', 'teacher'])
                ->onlyTrashed()
                ->paginate(10);

            if ($materials->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new MaterialCollection($materials),
                'Lấy tất cả thông tin tài liệu đã xóa thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($slug)
    {
        try {
            $material = $this->materialService->restoreMaterial($slug);

            return $this->successResponse(
                new MaterialResource($material),
                'Khôi phục tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug){
        try {
            $this->materialService->forceDeleteMaterial($slug);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn tài liệu thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
