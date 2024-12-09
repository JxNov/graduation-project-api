<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockMaterialRequest;
use App\Http\Requests\ClassMaterialRequest;
use App\Http\Resources\ClassMaterialResource;
use App\Models\Block;
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

    public function getBlockMaterial()
    {
        try {
            $blocks = Block::with(['subjects.materials'])->get();
            \Illuminate\Support\Facades\Log::info($blocks->toArray());

            $data = $blocks->map(function ($block) {
                return [
                    'blockName' => $block->name,
                    'blockSlug' => $block->slug,
                    'subjects' => $block->subjects->map(function ($subject) {
                        return [
                            'subjectName' => $subject->name,
                            'subjectSlug' => $subject->slug,
                            'materials' => $subject->materials->map(function ($material) {
                                return [
                                    'materialTitle' => $material->title,
                                    'materialSlug' => $material->slug,
                                    'description' => $material->description ?? null,
                                    'file_path' => $material->file_path,
                                    'teacherName' => $material->teacher->name,
                                    'teacherImage' => $material->teacher->image ?? null,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy danh sách tài liệu theo khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function storeForClass(ClassMaterialRequest $request)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->createNewMaterialForClass($data);

            return $this->successResponse(
                new ClassMaterialResource($material),
                'Tạo mới tài liệu thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateForClass(ClassMaterialRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->updateMaterialForClass($data, $slug);

            return $this->successResponse(
                new ClassMaterialResource($material),
                'Cập nhật tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function storeForBlock(BlockMaterialRequest $request)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->createNewMaterialForBlock($data);

            return $this->successResponse(
                new ClassMaterialResource($material),
                'Tạo mới tài liệu thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateForBlock(BlockMaterialRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $material = $this->materialService->updateMaterialForBlock($data, $slug);

            return $this->successResponse(
                new ClassMaterialResource($material),
                'Cập nhật tài liệu thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function download($slug)
    {
        try {
            return $this->materialService->downloadMaterial($slug);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
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
