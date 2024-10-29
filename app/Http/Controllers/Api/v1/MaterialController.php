<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialCollection;
use App\Models\Material;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class MaterialController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            $materials = Material::latest('id')
                ->select('title', 'description', 'file_path', 'subject_id', 'teacher_id')
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
}
