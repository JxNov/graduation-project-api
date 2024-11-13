<?php

use App\Http\Controllers\Api\v1\ModuleController;
use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\StudentClassController;
use App\Http\Controllers\Api\v1\StudentController;
use App\Http\Controllers\Api\v1\StudentRoleController;
use App\Http\Controllers\Api\v1\SubjectTeacherController;
use App\Http\Controllers\Api\v1\TeacherController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\BlockController;
use App\Http\Controllers\Api\v1\ChatController;
use App\Http\Controllers\Api\v1\ClassController;
use App\Http\Controllers\Api\v1\GenerationController;
use App\Http\Controllers\Api\v1\MaterialController;
use App\Http\Controllers\Api\v1\SemesterController;
use App\Http\Controllers\Api\v1\StudentExcelController;
use App\Http\Controllers\Api\v1\SubjectController;
use App\Http\Controllers\Api\v1\ScheduleController;
use App\Http\Controllers\Api\v1\TeacherExcelController;
use App\Http\Controllers\Api\v1\ScoreController;
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
        Route::patch('/assign-roles-permissions', [UserController::class, 'assignRolesAndPermissions']);
        Route::delete('/assign-roles-permissions', [UserController::class, 'revokeRolesAndPermissions']);
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

Route::prefix('materials')
    ->group(function () {
        Route::get('/', [MaterialController::class, 'index']);
        Route::post('/', [MaterialController::class, 'store']);
        Route::get('/trash', [MaterialController::class, 'trash']);
        Route::get('/{slug}', [MaterialController::class, 'show']);
        Route::patch('/{slug}', [MaterialController::class, 'update']);
        Route::delete('/{slug}', [MaterialController::class, 'destroy']);
        Route::get('/download/{slug}', [MaterialController::class, 'download']);
        Route::get('/restore/{slug}', [MaterialController::class, 'restore']);
        Route::delete('/force-delete/{slug}', [MaterialController::class, 'forceDelete']);
    });

// môn học
Route::prefix('subjects')
    ->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::get('/trash', [SubjectController::class, 'trash']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::patch('/{slug}', [SubjectController::class, 'update']);
        Route::delete('/{slug}', [SubjectController::class, 'destroy']);
        Route::get('/restore/{slug}', [SubjectController::class, 'restore']);
        Route::get('/forcedelete/{slug}', [SubjectController::class, 'forceDelete']);
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
        Route::prefix('students-class')
            ->group(function () {
                Route::post('import', [StudentClassController::class, 'importStudent']);
                Route::get('/', [StudentClassController::class, 'index']);
                Route::post('/', [StudentClassController::class, 'store']);
                Route::patch('/{id}', [StudentClassController::class, 'update']);
                Route::delete('/{id}', [StudentClassController::class, 'destroy']);
                Route::get('restore/{id}', [StudentClassController::class, 'restore']);
                Route::get('/trash', [StudentClassController::class, 'trash']);
                Route::delete('/forcedelete/{id}', [StudentClassController::class, 'forceDelete']);
                Route::get('export-by-generation/{slug}', [StudentClassController::class, 'exportStudentByGeneration']);
                Route::get('export-by-academic-year/{slug}', [StudentClassController::class, 'exportStudentByAcademicYear']);
            });
    });
Route::prefix('students')
    ->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::put('/{username}', [StudentController::class, 'update']);
        Route::delete('/{username}', [StudentController::class, 'destroy']);
        Route::get('/restore/{username}', [StudentController::class, 'restore']);
        Route::get('/show/{username}', [StudentController::class, 'show']);
    });
Route::prefix('teachers')
    ->group(function () {
        Route::get('/', [TeacherController::class, 'index']);
        Route::post('/', [TeacherController::class, 'store']);
        Route::put('/{username}', [TeacherController::class, 'update']);
        Route::delete('/{username}', [TeacherController::class, 'destroy']);
        Route::get('/restore/{username}', [TeacherController::class, 'restore']);
        Route::get('/show/{username}', [TeacherController::class, 'show']);

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
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/{classSlug}', [AttendanceController::class, 'studentInClass']);
        Route::post('/save', [AttendanceController::class, 'save']);
        Route::patch('/update/{id}', [AttendanceController::class, 'update']);
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

Route::prefix('chat')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('admin')
            ->group(function () {
                Route::post('/message-to-student/{username}', [ChatController::class, 'sendMessageToStudent']);
                Route::get('/conversations', [ChatController::class, 'getConversationAdmin']);
                Route::get('/conversation-message/{conversationID}', [ChatController::class, 'getMessageStudentToAdmin']);
            });

        Route::prefix('students')
            ->group(function () {
                Route::post('/message-to-admin', [ChatController::class, 'sendMessageToAdmin']);
                Route::get('/conversations', [ChatController::class, 'getConversationStudent']);
                Route::get('/conversation-message/{conversationID}', [ChatController::class, 'getMessageAdminToStudent']);
            });

        Route::patch('/update-message/{messageID}', [ChatController::class, 'updateMessage']);
    });
Route::prefix('subject-teachers')
    ->group(function () {
        Route::get('/', [SubjectTeacherController::class, 'index']);
        Route::get('/trash', [SubjectTeacherController::class, 'trash']);
        Route::post('/', [SubjectTeacherController::class, 'store']);
        //    Route::patch('/{id}', [SubjectTeacherController::class, 'update']);
        Route::delete('/{id}', [SubjectTeacherController::class, 'destroy']);
        Route::delete('forcedelete/{id}', [SubjectTeacherController::class, 'forceDelete']);
        Route::get('/restore/{id}', [SubjectTeacherController::class, 'restore']);
    });

//Điểm học sinh
Route::prefix('scores')
    ->group(function () {
        Route::get('/', [ScoreController::class, 'index']);
        Route::get('/{student_name}/{subject_slug}/{semester_slug}', [ScoreController::class, 'getScoreByStudentSubjectSemester']); //Lấy theo người dùng => môn học => kì học
        Route::post('/', [ScoreController::class, 'store']);
        Route::get('/{id}', [ScoreController::class, 'show']);
        Route::patch('/{id}', [ScoreController::class, 'update']);
        //        Route::delete('/{id}', [ScoreController::class, 'destroy']);
    });
