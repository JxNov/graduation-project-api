<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function successResponse($data, $message = null, $statusCode = 200): JsonResponse
    {
        if (empty($data)) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
            ], $statusCode);
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function errorResponse($e, $statusCode = 500): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'error' => $e,
        ], $statusCode);
    }
}
