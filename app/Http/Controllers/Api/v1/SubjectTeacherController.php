<?php

namespace App\Http\Controllers\Api\v1;


use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectTeacherRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\SubjectTeacherCollection;
use App\Http\Resources\SubjectTeacherResource;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Services\SubjectTeacherService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubjectTeacherController extends Controller
{
    use ApiResponseTrait;
    protected $SubjectTeacherService;

    public function __construct(SubjectTeacherService $SubjectTeacherService)
    {
        $this->SubjectTeacherService = $SubjectTeacherService;
    }
    public function index()
    {
        try {
            // Lấy các giáo viên đã có môn học dạy
            $subjectTeachers = User::whereHas('subjects') // Chỉ lấy giáo viên có môn học
                ->with('subjects') // Tải các môn học mà giáo viên dạy
                ->paginate(10);

            if ($subjectTeachers->isEmpty()) {
                return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                SubjectTeacherCollection::make($subjectTeachers), // Chuyển đổi dữ liệu qua resource collection
                'Lấy tất cả thông tin thành công',
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(SubjectTeacherRequest $request)
    {
        try {
            
            $subjectTeacher = $this->SubjectTeacherService->store($request->all());

            return  $this->successResponse(new SubjectTeacherResource($subjectTeacher), 'Thêm giáo viên dạy môn học thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    // public function update(SubjectTeacherRequest $request, $id)
    // {
    //     try {
    //         $user = $this->SubjectTeacherService->update($request->all(), $id);
    //         return $this->successResponse(new SubjectTeacherResource($user), 'Đổi giáo viên của môn học thành công!', Response::HTTP_OK);
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    public function destroy($id)
    {
        try {
            $user = $this->SubjectTeacherService->destroy($id);
            return $this->successResponse(null, 'Xoá thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function restore($id)
    {
        try {
            $user = $this->SubjectTeacherService->backup($id);
            return $this->successResponse(null, 'Khôi phục thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function trash()
{
    try {
        
    
    $subjectTeachers = User::whereHas('subjects', function ($query) {
            $query->whereNotNull('subject_teachers.deleted_at'); // Chỉ lấy bản ghi đã bị xóa mềm trong bảng trung gian
        })
        ->with(['subjects' => function ($query) {
            $query->whereNotNull('subject_teachers.deleted_at'); // Điều kiện xóa mềm từ bảng trung gian
        }])
        ->paginate();

        // Kiểm tra nếu không có dữ liệu
        if ($subjectTeachers->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }
        Log::info($subjectTeachers);
        return $this->successResponse(
            SubjectTeacherCollection::make($subjectTeachers), // Dữ liệu đã được chuyển qua resource collection
            'Lấy tất cả thông tin thành công',
            Response::HTTP_OK
        );
        
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), Response::HTTP_OK);
    }
    
}
public function forceDelete($id)
    {
        try {
            $this->SubjectTeacherService->forceDelete($id);
            return $this->successResponse(
                null,
                'Xóa vĩnh viễn thành công!',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_OK);
        }
    }
}
