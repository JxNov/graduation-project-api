<?php

use App\Http\Controllers\Api\v1\AcademicYearClassController;
use App\Http\Controllers\Api\v1\ClassMaterialController;
use App\Http\Controllers\Api\v1\ModuleController;
use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\StudentController;
use App\Http\Controllers\Api\v1\StudentRoleController;
use App\Http\Controllers\Api\v1\TeacherController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\BlockClassController;
use App\Http\Controllers\Api\v1\BlockController;
use App\Http\Controllers\Api\v1\BlockMaterialController;
use App\Http\Controllers\Api\v1\BlockSubjectController;
use App\Http\Controllers\Api\v1\ClassController;
use App\Http\Controllers\Api\v1\GenerationController;
use App\Http\Controllers\Api\v1\MaterialController;
use App\Http\Controllers\Api\v1\SemesterController;
use App\Http\Controllers\Api\v1\StudentExcelController;
use App\Http\Controllers\Api\v1\SubjectController;
use App\Http\Controllers\Api\v1\TeacherExcelController;
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
        Route::get('/trash', [AcademicYearClassController::class, 'trash']);
        Route::get('/{id}', [AcademicYearClassController::class, 'show']);
        Route::patch('/{id}', [AcademicYearClassController::class, 'update']);
        Route::delete('/{id}', [AcademicYearClassController::class, 'destroy']);
        Route::get('/restore/{id}', [AcademicYearClassController::class, 'restore']);
        Route::delete('/force-delete/{id}', [AcademicYearClassController::class, 'forceDelete']);
    });

Route::prefix('block-classes')
    ->group(function () {
        Route::get('/', [BlockClassController::class, 'index']);
        Route::post('/', [BlockClassController::class, 'store']);
        Route::get('/trash', [BlockClassController::class, 'trash']);
        Route::get('/{id}', [BlockClassController::class, 'show']);
        Route::patch('/{id}', [BlockClassController::class, 'update']);
        Route::delete('/{id}', [BlockClassController::class, 'destroy']);
        Route::get('/restore/{id}', [BlockClassController::class, 'restore']);
        Route::delete('/force-delete/{id}', [BlockClassController::class, 'forceDelete']);
    });

Route::prefix('materials')
    ->group(function () {
        Route::get('/', [MaterialController::class, 'index']);
        Route::post('/', [MaterialController::class, 'store']);
        Route::get('/trash', [MaterialController::class, 'trash']);
        Route::get('/{slug}', [MaterialController::class, 'show']);
        Route::patch('/{slug}', [MaterialController::class, 'update']);
        Route::delete('/{slug}', [MaterialController::class, 'destroy']);
        Route::get('/restore/{slug}', [MaterialController::class, 'restore']);
        Route::delete('/force-delete/{slug}', [MaterialController::class, 'forceDelete']);
    });

Route::prefix('block-materials')
    ->group(function () {
        Route::get('/', [BlockMaterialController::class, 'index']);
        Route::post('/', [BlockMaterialController::class, 'store']);
        Route::get('/trash', [BlockMaterialController::class, 'trash']);
        Route::get('/{id}', [BlockMaterialController::class, 'show']);
        Route::patch('/{id}', [BlockMaterialController::class, 'update']);
        Route::delete('/{id}', [BlockMaterialController::class, 'destroy']);
        Route::get('/restore/{id}', [BlockMaterialController::class, 'restore']);
        Route::delete('/force-delete/{id}', [BlockMaterialController::class, 'forceDelete']);
    });

Route::prefix('class-materials')
    ->group(function () {
        Route::get('/', [ClassMaterialController::class, 'index']);
        Route::post('/', [ClassMaterialController::class, 'store']);
        Route::get('/trash', [ClassMaterialController::class, 'trash']);
        Route::get('/{id}', [ClassMaterialController::class, 'show']);
        Route::patch('/{id}', [ClassMaterialController::class, 'update']);
        Route::delete('/{id}', [ClassMaterialController::class, 'destroy']);
        Route::get('/restore/{id}', [ClassMaterialController::class, 'restore']);
        Route::delete('/force-delete/{id}', [ClassMaterialController::class, 'forceDelete']);
    });

// môn học
Route::prefix('subjects')
    ->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::patch('/{slug}', [SubjectController::class, 'update']);
        Route::delete('/{slug}', [SubjectController::class, 'destroy']);
        Route::get('/restore/{slug}', [SubjectController::class, 'restore']);
    });

// môn học vào khoá học
Route::prefix('block-subjects')
    ->group(function () {
        Route::get('/', [BlockSubjectController::class, 'index']);
        Route::post('/', [BlockSubjectController::class, 'store']);
        Route::delete('/{id}', [BlockSubjectController::class, 'destroy']);
        Route::get('/restore/{id}', [BlockSubjectController::class, 'restore']);
    });

Route::prefix('excels')
    ->group(function () {

        Route::prefix('students')
            ->group(function () {
                Route::get('export-form', [StudentExcelController::class, 'exportStudentForm']);
                Route::post('import', [StudentExcelController::class, 'importStudent']);
                Route::get('export-by-generation/{slug}', [StudentExcelController::class, 'exportStudentByGeneration']);
                Route::get('export-by-academic-year/{slug}', [StudentExcelController::class, 'exportStudentByAcademicYear']);
            });

        Route::prefix('teachers')
            ->group(function () {
                Route::get('export-form', [TeacherExcelController::class, 'exportTeacherForm']);
                Route::post('import', [TeacherExcelController::class, 'importTeacher']);
            });
    });
Route::prefix('students')
    ->group(function () {
        Route::get('/', [StudentController::class, 'index']); // Lấy danh sách học sinh
        Route::post('/', [StudentController::class, 'store']); // Tạo học sinh mới
        Route::put('/{username}', [StudentController::class, 'update']); // Cập nhật học sinh theo id
        Route::delete('/{username}', [StudentController::class, 'destroy']); // Xóa mềm học sinh theo id
        Route::get('/restore/{username}', [StudentController::class, 'restore']); // Khôi phục học sinh đã xóa mềm
    });
Route::prefix('teachers')
    ->group(function () {
        Route::get('/', [TeacherController::class, 'index']); // Lấy danh sách học sinh
        Route::post('/', [TeacherController::class, 'store']); // Tạo học sinh mới
        Route::put('/{username}', [TeacherController::class, 'update']); // Cập nhật học sinh theo id
        Route::delete('/{username}', [TeacherController::class, 'destroy']); // Xóa mềm học sinh theo id
        Route::get('/restore/{username}', [TeacherController::class, 'restore']); // Khôi phục học sinh đã xóa mềm
    });

Route::prefix('students-role')
    ->group(function () {
        Route::get('/', [StudentRoleController::class, 'index']);
        Route::post('/', [StudentRoleController::class, 'store']);
        Route::put('/{username}', [StudentRoleController::class, 'update']);
        Route::delete('/{username}/{slugRole}', [StudentRoleController::class, 'destroy']);
    });

Route::prefix('attendances')
    ->group(function () {
        Route::get('/{classSlug}', [AttendanceController::class, 'studentInClass']);
        Route::post('/save', [AttendanceController::class, 'save']);
        Route::patch('/update/{id}', [AttendanceController::class, 'update']);
    });