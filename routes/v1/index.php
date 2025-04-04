<?php

use App\Http\Controllers\Api\v1\ArticleController;
use App\Http\Controllers\Api\v1\AssignmentController;
use App\Http\Controllers\Api\v1\ModuleController;
use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\StatisticController;
use App\Http\Controllers\Api\v1\StudentClassController;
use App\Http\Controllers\Api\v1\StudentController;
use App\Http\Controllers\Api\v1\SubjectTeacherController;
use App\Http\Controllers\Api\v1\SubmittedAssignmentController;
use App\Http\Controllers\Api\v1\TeacherController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\AcademicYearController;
use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\BlockController;
use App\Http\Controllers\Api\v1\ChatbotController;
use App\Http\Controllers\Api\v1\ChatController;
use App\Http\Controllers\Api\v1\ClassController;
use App\Http\Controllers\Api\v1\ClassroomController;
use App\Http\Controllers\Api\v1\CommentController;
use App\Http\Controllers\Api\v1\GenerationController;
use App\Http\Controllers\Api\v1\MaterialController;
use App\Http\Controllers\Api\v1\NotificationController;
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
        Route::get('/trash', [UserController::class, 'trash']);
        Route::get('/showUser/{username}', [UserController::class, 'showUser']);
        Route::get('/{username}/roles', [UserController::class, 'getUserRoles']);
        Route::patch('/{username}/roles', [UserController::class, 'assignRoles']);
        Route::delete('/{username}/roles', [UserController::class, 'revokeRoles']);
        Route::get('/{username}/permissions', [UserController::class, 'getUserPermissions']);
        Route::patch('/{username}/permissions', [UserController::class, 'assignPermissions']);
        Route::delete('/{username}/permissions', [UserController::class, 'revokePermissions']);
        Route::patch('/assign-roles-permissions', [UserController::class, 'assignRolesAndPermissions']);
        Route::delete('/assign-roles-permissions', [UserController::class, 'revokeRolesAndPermissions']);
        Route::patch('/updateUser/{username}', [UserController::class, 'updateUser']);
        Route::patch('/change-info/{username}', [UserController::class, 'changeInfo']);
        Route::patch('/restore/{username}', [UserController::class, 'restoreUser']);
        Route::delete('/{username}', [UserController::class, 'destroy']);
        Route::patch('/forgot-password', [UserController::class, 'forgotPassword']);
    });

// khóa học sinh
Route::prefix('generations')
    ->group(function () {
        Route::get('/', [GenerationController::class, 'index']);
        Route::post('/', [GenerationController::class, 'store']);
        Route::post('/assign-student', [GenerationController::class, 'assignStudentGeneration']);
        Route::get('/trash', [GenerationController::class, 'trash']);
        Route::get('/{slug}', [GenerationController::class, 'show']);
        Route::patch('/{slug}', [GenerationController::class, 'update']);
        Route::delete('/{slug}', [GenerationController::class, 'destroy']);
        Route::patch('/restore/{slug}', [GenerationController::class, 'restore']);
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
        Route::patch('/restore/{slug}', [AcademicYearController::class, 'restore']);
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
        Route::patch('/restore/{slug}', [SemesterController::class, 'restore']);
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
        Route::get('/getfailedstudents', [ClassController::class, 'getFailedStudents']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/trash', [ClassController::class, 'trash']);
        Route::get('/{slug}', [ClassController::class, 'show']);
        Route::get('/get-semester/{slug}', [ClassController::class, 'getSemesterByAcademicYear']);
        Route::get('/teacher/{username}', [ClassController::class, 'getClassOfTeacher']);
        Route::get('/student/{username}', [ClassController::class, 'getClassOfStudent']);
        Route::patch('/{slug}', [ClassController::class, 'update']);
        Route::delete('/{slug}', [ClassController::class, 'destroy']);
        Route::post('/assign-class/{slug}', [ClassController::class, 'assignClassToTeacher']);
        Route::patch('/restore/{slug}', [ClassController::class, 'restore']);
        Route::delete('/force-delete/{slug}', [ClassController::class, 'forceDelete']);
        Route::post('/promote-student/{slug}', [ClassController::class, 'promoteStudent']);
    });

Route::prefix('materials')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('classes')
            ->group(function () {
                Route::post('/', [MaterialController::class, 'storeForClass']);
                Route::patch('/{slug}', [MaterialController::class, 'updateForClass']);
            });

        Route::prefix('blocks')
            ->group(function () {
                Route::get('/', [MaterialController::class, 'getBlockMaterial']);
                Route::post('/', [MaterialController::class, 'storeForBlock']);
                Route::patch('/{slug}', [MaterialController::class, 'updateForBlock']);
            });

        Route::get('/download/{slug}', [MaterialController::class, 'download']);
        Route::delete('/force-delete/{slug}', [MaterialController::class, 'forceDelete']);
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
                Route::get('/surplusStudents', [StudentClassController::class, 'surplusStudents']); // học sinh không có lớp, chưa được phân lớp sau khi dùng distribute 
                Route::post('/', [StudentClassController::class, 'store']);
                Route::post('/distributeStudents/{academic_year_slug}/{blockSlug}', [StudentClassController::class, 'distributeStudents']);
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
        Route::patch('/{username}', [StudentController::class, 'update']);
        Route::delete('/{username}', [StudentController::class, 'destroy']);
        Route::get('/restore/{username}', [StudentController::class, 'restore']);
        Route::get('/show/{username}', [StudentController::class, 'show']);
    });

Route::prefix('teachers')
    ->group(function () {
        Route::get('/', [TeacherController::class, 'index']);
        Route::post('/', [TeacherController::class, 'store']);
        Route::patch('/{username}', [TeacherController::class, 'update']);
        Route::delete('/{username}', [TeacherController::class, 'destroy']);
        Route::get('/restore/{username}', [TeacherController::class, 'restore']);
        Route::get('/show/{username}', [TeacherController::class, 'show']);
    });

Route::prefix('attendances')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/show', [AttendanceController::class, 'attendanceOfStudent']); // học sinh xem điểm danh
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/{classSlug}', [AttendanceController::class, 'studentInClass']);
        Route::post('/', [AttendanceController::class, 'save']);
        Route::patch('/student/{username}', [AttendanceController::class, 'updateStudentAttendance']);
        Route::patch('/{id}', [AttendanceController::class, 'update']);
    });

// Thời khóa biểu
Route::prefix('schedules')
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::patch('/{classSlug}', [ScheduleController::class, 'update']);
        Route::get('/student', [ScheduleController::class, 'scheduleOfStudent']); // học sinh xem lịc học của mihf
        Route::get('/teacher', [ScheduleController::class, 'scheduleOfTeacher']); // giaso vien xem lịc dạy
        Route::post('{blockSlug}', [ScheduleController::class, 'store']);
        Route::get('/{classSlug}', [ScheduleController::class, 'show']); // xem lịch học của 1 lớp
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
        Route::patch('/{username}', [SubjectTeacherController::class, 'update']);
        Route::delete('/{id}', [SubjectTeacherController::class, 'destroy']);
        Route::delete('forcedelete/{id}', [SubjectTeacherController::class, 'forceDelete']);
        Route::get('/restore/{id}', [SubjectTeacherController::class, 'restore']);
    });

//Điểm học sinh
Route::prefix('scores')
    ->group(function () {
        Route::get('/', [ScoreController::class, 'index']);
        Route::get('/{student_name}/{subject_slug}/{class_slug}/{semester_slug}', [ScoreController::class, 'getScoreByStudentSubjectSemester']); //Lấy theo người dùng => môn học => lớp học => kì học
        Route::post('/', [ScoreController::class, 'store']);
        // Route::get('/{id}', [ScoreController::class, 'show']);
        // Route::patch('/{id}', [ScoreController::class, 'update']);
        //        Route::delete('/{id}', [ScoreController::class, 'destroy']);
        Route::get('/list/{class_slug}', [ScoreController::class, 'calculateAndSaveFinalScores']);
        Route::get('/classes', [ScoreController::class, 'getScoreByAdmin']);
        Route::get('/classes/teacher', [ScoreController::class, 'getScoreByTeacher']);
        Route::get('/student', [ScoreController::class, 'getScoreByStudent']);
    });

// Classroom
Route::prefix('classrooms')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/class-material/{slug}', [ClassroomController::class, 'getClassMaterialClassroom']);
        Route::get('/student', [ClassroomController::class, 'getClassroomForStudent']);
        Route::get('/', [ClassroomController::class, 'getClassroomForTeacher']);
        Route::get('/{slug}', [ClassroomController::class, 'getDetailClassroomForTeacher']);
        Route::get('/assignment/{slug}', [ClassroomController::class, 'getAssignmentClassroom']);
        Route::get('/people/{slug}', [ClassroomController::class, 'getStudentClassroom']);
        Route::post('/', [ClassroomController::class, 'joinClassroomByCode']); // vào lớp = mã lớp
    });

//Thống kê
Route::prefix('statistic')
    ->group(function () {
        Route::get('/countall', [StatisticController::class, 'countAll']); // thống kê tổng số lượng học sinh, giáo viên, lớp học, môn học
        Route::get('/performation/{academicYearSlug}', [StatisticController::class, 'getPerformationLevelAll']);// thống kê học lực của tất cả học sinh
        Route::get('/list/{block_slug}', [StatisticController::class, 'StudentClassInBlock']); // số lượng học sinh theo từng khối
        Route::get('/gender', [StatisticController::class, 'getGenderRatioInGeneration']);// tỉ lệ giới tính theo từng khoá
        Route::get('/final/{classSlug}/{yearSlug}', [StatisticController::class, 'calculateFinalScoreYearClass']); // điểm tổng kết của cả lớp theo năm, chỉ lưu vào final_score khi có kết quả của năm
        Route::get('/class-semester/{semester_slug}', [StatisticController::class, 'getStatisticAllClassInSemester']); // thống kê điểm TB của tất cả lớp theo kỳ
        Route::get('{subject_slug}/{class_slug}/{semester_slug}', [StatisticController::class, 'getStatisticByClassSubjectSemester']);
        Route::get('/{class_slug}/{semester_slug}', [StatisticController::class, 'getStatisticByClassSemester']); // thống kê điểm TB của 1 lớp theo kỳ
        Route::get('/{academic_year_slug}', [StatisticController::class, 'countStudentInBlockByAcademicYear']); // số lượng học sinh theo từng khối của năm
        Route::get('/list/{classSlug}/{semesterSlug}/{yearSlug}', [StatisticController::class, 'showStudentScoreSemesterClass']); // điểm chi tiết của cả lớp học theo kì
        Route::get('/user/{classSlug}/{semesterSlug}/{yearSlug}', [StatisticController::class, 'showStudentScoreSemester']); // điểm chi tiết  của 1 học sinh theo kì
    });

//Assignment
Route::prefix('assignments')->group(function () {
    Route::get('/classes/{classSlug}', [AssignmentController::class, 'index']);
    Route::post('/', [AssignmentController::class, 'store']);
    Route::get('/classes/{classSlug}/{assignmentSlug}', [AssignmentController::class, 'show']);
    Route::patch('/{assignmentSlug}', [AssignmentController::class, 'update']);
    Route::delete('/{assignmentSlug}', [AssignmentController::class, 'destroy']);
    Route::get('/trash', [AssignmentController::class, 'trash']);
    Route::patch('/restore/{assignmentSlug}', [AssignmentController::class, 'restore']);
    Route::delete('/force-delete/{assignmentSlug}', [AssignmentController::class, 'forceDelete']);
});

//Submitted Assignment
Route::prefix('submitted_assignments')->group(function () {
    Route::get('/show', [SubmittedAssignmentController::class, 'showAssignmentStudent']);
    Route::get('/show/teachersubject/{classSlug}', [SubmittedAssignmentController::class, 'showAssignmentsForTeacher']);
    Route::post('/{assignmentSlug}', [SubmittedAssignmentController::class, 'store']);
    Route::get('/{classSlug}/{assignmentSlug}/submitted-assignments', [SubmittedAssignmentController::class, 'getAllSubmittedAssignments']); //Hiển thị toàn bộ danh sách submitted Assignment
    //Route::put('/{id}', [SubmittedAssignmentController::class, 'update']);
    Route::patch('/{assignmentSlug}/{studentName}/score-feedback', [SubmittedAssignmentController::class, 'updateScoreAndFeedback']);
});

Route::prefix('articles')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::post('/', [ArticleController::class, 'store']);
        Route::delete('/force-delete/{id}', [ArticleController::class, 'forceDelete']);
    });

Route::prefix('comments')
    ->middleware('auth:api')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store']);
        Route::patch('/{id}', [CommentController::class, 'update']);
        Route::delete('/{id}', [CommentController::class, 'destroy']);
    });

// Thông báo
Route::prefix('notifications')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{notificationId}', [NotificationController::class, 'markAsRead']);
    });

// Chat bot
Route::prefix('chat-bots')
    ->group(function () {
        Route::get('/', [ChatbotController::class, 'index']);
        Route::post('/', action: [ChatbotController::class, 'ask']);
    });
