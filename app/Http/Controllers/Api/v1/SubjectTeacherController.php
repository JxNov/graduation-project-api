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
        $subjects = DB::table('subject_teachers')->select('id','teacher_id','subject_id')->paginate(10); 

        if ($subjects->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        // Trả về dữ liệu qua SubjectTeacherCollection
        return $this->successResponse(
             new SubjectTeacherCollection($subjects),
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
        // Gọi service để xử lý logic
        $subjectTeachers = $this->SubjectTeacherService->store($request->all());

        // Trả về danh sách các giáo viên đã thêm thành công
        return $this->successResponse(
            SubjectTeacherResource::collection($subjectTeachers),
            'Thêm giáo viên dạy môn học thành công',
            Response::HTTP_CREATED
        );
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage());
    }
}

    public function update(SubjectTeacherRequest $request, $id)
    {
        try {
            $user = $this->SubjectTeacherService->update($request->all(), $id);
            return $this->successResponse(new SubjectTeacherResource($user), 'Đổi giáo viên của môn học thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
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
        $subjects = DB::table('subject_teachers')->select('id','teacher_id','subject_id')->whereNotNull('subject_teachers.deleted_at')->paginate(10); 

        if ($subjects->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        // Trả về dữ liệu qua SubjectTeacherCollection
        return $this->successResponse(
            SubjectTeacherCollection::make($subjects),
            'Lấy tất cả thông tin thành công',
            Response::HTTP_OK
        );
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
