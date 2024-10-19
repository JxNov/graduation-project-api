<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockRequest;
use App\Http\Requests\BlockSubjectRequest;
use App\Http\Resources\BlockSubjectResource;
use App\Http\Resources\SubjectResource;
use App\Models\Block;
use App\Models\Subject;
use App\Services\BlockSubjectService;
use App\Services\SubjectService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlockSubjectController extends Controller
{
    use ApiResponseTrait;
    protected $blocksubjectservice;
    public function __construct(BlockSubjectService $blocksubjectservice)
    {
        $this->blocksubjectservice = $blocksubjectservice;
    }
    public function index()
    {
        // Lấy tất cả các bản ghi từ bảng block_subject
        $list = Block::with('subjects')->get();

        // Trả về dữ liệu sử dụng BlockSubjectResource
        return BlockSubjectResource::collection($list);
    }

    public function store(BlockSubjectRequest $request)
    {
        try {

            $validatedData = $request->validated();
            $this->blocksubjectservice->store($validatedData); // Gọi phương thức store
            return $this->successResponse(null, 'Môn học đã được thêm vào khối thành công', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function destroy($id)
    {
        try {
            $blocksubject = $this->blocksubjectservice->destroy($id);
            return $this->successResponse($blocksubject, 'SUCCESS', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
}
