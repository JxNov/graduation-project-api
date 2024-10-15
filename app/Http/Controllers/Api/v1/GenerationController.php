<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGenerationRequest;
use App\Http\Requests\GenerationRequest;
use App\Http\Requests\UpdateGenerationRequest;
use App\Http\Resources\GenerationCollection;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Services\GenerationService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GenerationController extends Controller
{
    use ApiResponseTrait;

    protected $generationService;

    public function __construct(GenerationService $generationService)
    {
        $this->generationService = $generationService;
    }

    public function index()
    {
        $generations = Generation::select('id', 'name', 'slug', 'start_date', 'end_date')->latest('id')->paginate(6);

        if ($generations->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new GenerationCollection($generations),
            'Lấy tất cả thông tin khóa học thành công',
            Response::HTTP_OK
        );
    }

    public function show($slug)
    {
        $generation = Generation::select('id', 'name', 'start_date', 'end_date')->where('slug', $slug)->first();

        if ($generation === null) {
            return $this->errorResponse('Không tìm thấy khóa học', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new GenerationResource($generation),
            'Đã lấy thành công chi tiết khóa học',
            Response::HTTP_OK
        );
    }

    public function store(GenerationRequest $request)
    {
        try {
            $data = $request->validated();

            $generation = $this->generationService->createNewGeneration($data);

            if ($generation) {
                return $this->successResponse(
                    new GenerationResource($generation),
                    'Đã thêm thành công khóa học mới',
                    Response::HTTP_CREATED
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(GenerationRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $generation = $this->generationService->updateGeneration($data, $slug);

            return $this->successResponse(
                new GenerationResource($generation),
                'Đã cập nhật khóa học thành công',
                statusCode: Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        // Log::info(__FUNCTION__);
        // Log::info($slug);

        try {
            $this->generationService->deleteGeneration($slug);

            return $this->successResponse(
                null,
                'Đã xóa khóa học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $generations = Generation::onlyTrashed()
            ->select('id', 'name', 'slug', 'start_date', 'end_date')
            ->paginate(6);

        if ($generations->isEmpty()) {
            return $this->successResponse(
                null,
                'Không có dữ liệu',
                Response::HTTP_OK
            );
        }

        return $this->successResponse(
            new GenerationCollection($generations),
            'Lấy tất cả thông tin khóa học đã xóa',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $generation = $this->generationService->restoreGeneration($slug);

            return $this->successResponse(
                new GenerationResource($generation),
                'Đã khôi phục khóa học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // xóa vĩnh viễn
    public function forceDelete($slug)
    {
        try {
            $this->generationService->forceDeleteGeneration($slug);

            return $this->successResponse(
                null,
                'Đã xóa khóa học vĩnh viễn',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
