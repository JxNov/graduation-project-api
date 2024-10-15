<?php

use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\GenerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->group(function () {
        // khóa học sinh
        Route::prefix('generations')
            ->group(function () {
            Route::get('/', [GenerationController::class, 'index']);
            Route::post('/', [GenerationController::class, 'store']);
            Route::get('/trash', [GenerationController::class, 'trash']);
            Route::get('/{slug}', [GenerationController::class, 'show']);
            Route::patch('/{slug}', [GenerationController::class, 'update']);
            Route::delete('/{slug}', [GenerationController::class, 'destroy']);
            Route::get('/restore/{slug}', [GenerationController::class, 'restore']);
            Route::delete('/force-delete/{slug}', [GenerationController::class, 'forceDelete']);
        });

        // năm học
        Route::prefix('academic-years')
            ->group(function () {
            Route::get('/', [AcademicYearController::class, 'index']);
            Route::get('/create', [AcademicYearController::class, 'create']);
            Route::post('/', [AcademicYearController::class, 'store']);
            Route::get('/trash', [AcademicYearController::class, 'trash']);
            Route::get('/{slug}', [AcademicYearController::class, 'show']);
            Route::get('/edit/{slug}', [AcademicYearController::class, 'edit']);
            Route::patch('/{slug}', [AcademicYearController::class, 'update']);
            Route::delete('/{slug}', [AcademicYearController::class, 'destroy']);
            Route::get('/restore/{slug}', [AcademicYearController::class, 'restore']);
            Route::delete('/force-delete/{slug}', [AcademicYearController::class, 'forceDelete']);
        });
    });