<?php

use App\Http\Controllers\Api\v1\GenerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->group(function () {
        // khóa học sinh
        Route::prefix('generations')
            ->group(function () {
                Route::get('/', [GenerationController::class, 'index']);
                Route::post('/', [GenerationController::class, 'store']);
                Route::get('/{slug}', [GenerationController::class, 'show']);
                Route::patch('/{slug}', [GenerationController::class, 'update']);
                Route::delete('/{slug}', [GenerationController::class, 'destroy']);
            });

    });