<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\CreateStudentRequest;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;

class StudentController extends Controller
{
    use ApiResponseTrait;
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }
    public function index()
    {
        $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();
        $students = User::whereHas('roles', function ($query) use ($roleStudent) {
            $query->where('role_id', $roleStudent->id);
        })
            ->get();
        return StudentResource::collection($students);
    }
    public function store(CreateStudentRequest $request)
    {
        try {
            $data = $request->all();
            // Gọi service để tạo học sinh mới
            $student = $this->studentService->createStudent($data);

            return  $this->successResponse(new StudentResource($student), 'Thêm học sinh mới thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function update(UpdateStudentRequest $request, $username)
    {
        try {
            $user = $this->studentService->updateStudent($request->all(), $username);
            return $this->successResponse(new StudentResource($user), 'Sửa học sinh thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function destroy($username)
    {
        try {
            $user = $this->studentService->destroy($username);
            return $this->successResponse(null, 'Xoá học sinh thành công!', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function restore($username){
        try{
            $user = $this->studentService->backup($username);
            return $this->successResponse(null,'Khôi phục học sinh thành công!',Response::HTTP_OK);
        }catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function show($username){
        $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();
        $students = User::whereHas('roles', function ($query) use ($roleStudent) {
            $query->where('role_id', $roleStudent->id);
        })
        ->where('username',$username)
            ->first();
        return new StudentResource($students);
    }
}
