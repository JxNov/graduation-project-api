<?php

use App\Http\Controllers\Api\v1\AcademicYearClassController;
use App\Http\Controllers\Api\v1\ModuleController;
use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\BlockClassController;
use App\Http\Controllers\Api\v1\BlockController;
use App\Http\Controllers\Api\v1\BlockSubjectController;
use App\Http\Controllers\Api\v1\ClassController;
use App\Http\Controllers\Api\v1\GenerationController;
use App\Http\Controllers\Api\v1\SemesterController;
use App\Http\Controllers\Api\v1\StudentExcelController;
use App\Http\Controllers\Api\v1\SubjectController;
use App\Http\Controllers\Api\v1\ScheduleController;
use Illuminate\Support\Facades\Route;

// Module Permissions
Route::get('/modules', [ModuleController::class, 'index']);

// Vai trò
Route::prefix('roles')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::patch('/{slug}', [RoleController::class, 'update']);
        Route::delete('/{slug}', [RoleController::class, 'destroy']);
        Route::patch('/{name}/restore', [RoleController::class, 'restore']);
        Route::delete('/{slug}/force-delete', [RoleController::class, 'forceDelete']);
    });


// Quyền
Route::prefix('permissions')
    ->group(function () {
        Route::middleware(['auth:api', 'permission:users.read'])->get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::delete('/{slug}', [PermissionController::class, 'destroy']);
    });

// Người dùng
Route::prefix('users')
    ->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{username}/roles', [UserController::class, 'getUserRoles']);
        Route::patch('/{username}/roles', [UserController::class, 'assignRoles']);
        Route::delete('/{username}/roles', [UserController::class, 'revokeRoles']);
        Route::get('/{username}/permissions', [UserController::class, 'getUserPermissions']);
        Route::patch('/{username}/permissions', [UserController::class, 'assignPermissions']);
        Route::delete('/{username}/permissions', [UserController::class, 'revokePermissions']);
    });

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

Route::prefix('academic-year-classes')
    ->group(function () {
        Route::get('/', [AcademicYearClassController::class, 'index']);
        Route::post('/', [AcademicYearClassController::class, 'store']);
        Route::get('/{id}', [AcademicYearClassController::class, 'show']);
        Route::patch('/{id}', [AcademicYearClassController::class, 'update']);
        Route::delete('/{id}', [AcademicYearClassController::class, 'destroy']);
    });

Route::prefix('block-classes')
    ->group(function () {
        Route::get('/', [BlockClassController::class, 'index']);
        Route::post('/', [BlockClassController::class, 'store']);
        Route::get('/{id}', [BlockClassController::class, 'show']);
        Route::patch('/{id}', [BlockClassController::class, 'update']);
        Route::delete('/{id}', [BlockClassController::class, 'destroy']);
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
    ->group(function () {
        Route::get('export-student-form', [StudentExcelController::class, 'exportStudentForm']);
        Route::post('import-student', [StudentExcelController::class, 'importStudent']);
    });

// Thời khóa biểu
Route::prefix('schedules')
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::patch('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    });
