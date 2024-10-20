<?php

use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\BlockController;
use App\Http\Controllers\Api\v1\BlockSubjectController;
use App\Http\Controllers\Api\v1\ClassController;
use App\Http\Controllers\Api\v1\GenerationController;
use App\Http\Controllers\Api\v1\SemesterController;
use App\Http\Controllers\Api\v1\StudentExcelController;
use App\Http\Controllers\Api\v1\SubjectController;

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

        // kỳ học
        Route::prefix('semesters')
            ->group(function () {
                Route::get('/', [SemesterController::class, 'index']);
                Route::get('/create', [SemesterController::class, 'create']);
                Route::post('/', [SemesterController::class, 'store']);
                Route::get('/trash', [SemesterController::class, 'trash']);
                Route::get('/{slug}', [SemesterController::class, 'show']);
                Route::get('/edit/{slug}', [SemesterController::class, 'edit']);
                Route::patch('/{slug}', [SemesterController::class, 'update']);
                Route::delete('/{slug}', [SemesterController::class, 'destroy']);
                Route::get('/restore/{slug}', [SemesterController::class, 'restore']);
                Route::delete('/force-delete/{slug}', [SemesterController::class, 'forceDelete']);
            });

        // khối
        Route::prefix('blocks')
            ->group(function () {
                Route::get('/', [BlockController::class, 'index']);
                Route::post('/', [BlockController::class, 'store']);
                Route::get('/trash', [BlockController::class, 'trash']);
                Route::get('/{slug}', [BlockController::class, 'show']);
                Route::patch('/{slug}', [BlockController::class, 'update']);
                Route::delete('/{slug}', [BlockController::class, 'destroy']);
                Route::get('/restore/{slug}', [BlockController::class, 'restore']);
                Route::delete('/force-delete/{slug}', [BlockController::class, 'forceDelete']);
            });

        // lớp
        Route::prefix('classes')
            ->group(function () {
                Route::get('/', [ClassController::class, 'index']);
                Route::post('/', [ClassController::class, 'store']);
                Route::get('/trash', [ClassController::class, 'trash']);
                Route::get('/{slug}', [ClassController::class, 'show']);
                Route::patch('/{slug}', [ClassController::class, 'update']);
                Route::delete('/{slug}', [ClassController::class, 'destroy']);
                Route::get('/restore/{slug}', [ClassController::class, 'restore']);
                Route::delete('/force-delete/{slug}', [ClassController::class, 'forceDelete']);
            });

        // môn học
        Route::prefix('subjects')
            ->group(function () {
                Route::get('/', [SubjectController::class, 'index']);
                Route::post('/', [SubjectController::class, 'store']);
                Route::patch('/{id}', [SubjectController::class, 'update']);
                Route::delete('/{id}', [SubjectController::class, 'destroy']);
                Route::get('/restore/{id}', [SubjectController::class, 'restore']);
            });

        // môn học vào khoá học
        Route::prefix('blocksubjects')
            ->group(function () {
                Route::get('/', [BlockSubjectController::class, 'index']);
                Route::post('/', [BlockSubjectController::class, 'store']);
                Route::delete('/{id}', [BlockSubjectController::class, 'destroy']);
                Route::get('/restore/{id}', [BlockSubjectController::class, 'restore']);
            });

        Route::prefix('excel')
            ->group(function() {
                Route::get('export-student-form', [StudentExcelController::class, 'exportStudentForm']);
            });
    });
