<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassRequest;
use App\Http\Resources\ClassCollection;
use App\Http\Resources\ClassResource;
use App\Models\Classes;
use App\Services\ClassService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClassController extends Controller
{
    use ApiResponseTrait;

    protected $classService;

    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    public function index()
    {
        $classes = Classes::select('id', 'name', 'slug', 'code', 'teacher_id')
            ->latest('id')
            ->with('teacher')
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new ClassCollection($classes),
            'Lấy tất cả thông tin lớp học thành công',
            Response::HTTP_OK
        );
    }

    public function store(ClassRequest $request)
    {
        try {
            $data = $request->validated();

            $class = $this->classService->createNewClass($data);

            return $this->successResponse(
                new ClassResource($class),
                'Thêm lớp học thành công',
                Response::HTTP_CREATED
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($slug)
    {
        $class = Classes::where('slug', $slug)
            ->with('teacher')
            ->first();

        if ($class === null) {
            return $this->errorResponse(
                'Không tìm thấy lớp học',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            new ClassResource($class),
            'Lấy thông tin lớp học thành công',
            Response::HTTP_OK
        );
    }

    public function update(ClassRequest $request, $slug)
    {
        try {
            $data = $request->validated();

            $class = $this->classService->updateClass($data, $slug);

            return $this->successResponse(
                new ClassResource($class),
                'Cập nhật thông tin lớp học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function assignClassToTeacher(Request $request, $slug)
    {
        try {
            $data = $request->validate(
                [
                    'username' => 'required',
                    'username.*' => 'exists:users,username'
                ],
                [
                    'username.required' => 'Hãy chọn giáo viên',
                    'username.exists' => 'Tên giáo viên không tồn tại',
                ]
            );

            $this->classService->assignClassToTeacher($data, $slug);

            return $this->successResponse(
                null,
                'Phân công giáo viên thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->classService->deleteClass($slug);

            return $this->successResponse(
                null,
                'Xóa lớp học thành công',
                Response::HTTP_NO_CONTENT
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        $classes = Classes::select('id', 'name', 'slug', 'teacher_id')
            ->latest('id')
            ->with('teacher')
            ->onlyTrashed()
            ->paginate(10);

        if ($classes->isEmpty()) {
            return $this->errorResponse(
                'Không có dữ liệu',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            ClassResource::collection($classes),
            'Lấy tất cả thông tin lớp học đã xóa thành công',
            Response::HTTP_OK
        );
    }

    public function restore($slug)
    {
        try {
            $class = $this->classService->restoreClass($slug);

            return $this->successResponse(
                new ClassResource($class),
                'Phục hồi lớp học thành công',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->classService->forceDeleteClass($slug);

            return $this->successResponse(
                null,
                'Xóa vĩnh viễn lớp học thành công',
                Response::HTTP_NO_CONTENT
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
