<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentRoleResource;
use App\Models\User;
use App\Services\StudentRoleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentRoleController extends Controller
{
    use ApiResponseTrait;

    protected $studentService;
    public function __construct(StudentRoleService $studentService)
    {
        $this->studentService = $studentService;
    }
    public function index(){
        $listStudent = User::whereHas('roles',function($query){
            $query->where('slug','student');
        })->get();
        return StudentRoleResource::collection($listStudent);
    }
    public function store(Request $request){
        
        $data = [
            'username'=>$request->username,
            'slugRole'=>$request->slugRole
        ];
        try{
            $user = $this->studentService->store($data);
            return $this->successResponse($user,'SUccesss',Response::HTTP_CREATED);
        }
        catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update(Request $request, $username)
{
    // Validate dữ liệu đầu vào
    $request->validate([
        'username' => 'required|string|max:255',
        'slugRole' => 'required|string|max:255'
    ]);

    $data = [
        'username' => $request->username,
        'slugRole' => $request->slugRole
    ];

    try {
        // Cập nhật thông tin người dùng
        $user = $this->studentService->update($data, $username);
        return $this->successResponse($user, 'Cập nhật thành công', Response::HTTP_OK);
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function destroy($username, $slugRole)
{
    try {
        // Xóa vai trò của người dùng
        $user = $this->studentService->destroy($username, $slugRole);
        return $this->successResponse($user, 'Xóa vai trò thành công', Response::HTTP_OK);
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}
