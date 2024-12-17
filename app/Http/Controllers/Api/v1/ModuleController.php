<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Models\Module;
use App\Traits\ApiResponseTrait;

class ModuleController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        try {
            $modules = Module::select('name', 'title')->get();

            if ($modules->isEmpty()) {
                throw new \Exception('Không tìm thấy module nào.', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse($modules, 'Danh sách module được lấy thành công.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
