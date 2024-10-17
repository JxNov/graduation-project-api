<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectController extends Controller
{
    use ApiResponseTrait;

    protected $subjectservice;

    public function __construct(SubjectService $subjectservice)
    {
        $this->subjectservice = $subjectservice;
    }

    public function index()
    {
        $list = Subject::all();
        return SubjectResource::collection($list);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:50', 'unique:subjects'],
            'description' => ['required', 'max:500', 'string', 'min:10'],
            'block_level' => ['required', 'integer', 'between:6,9']
        ], [
            'name.required' => 'Tên môn học là bắt buộc.',
            'name.max' => 'Tên môn học không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên môn học đã tồn tại, vui lòng chọn tên khác.',

            'description.required' => 'Mô tả là bắt buộc.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.min' => 'Mô tả phải có ít nhất 10 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',

            'block_level.required' => 'Khối là bắt buộc.',
            'block_level.integer' => 'Khối phải là số nguyên.',
            'block_level.between' => 'Khối chỉ được nhập số từ 6 đến 9.'
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'block_level' => $request->block_level
        ];

        try {
            $subject = $this->subjectservice->store($data);
            return $this->successResponse($subject, 'SUCCESS', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'max:50', 'unique:subjects'],
            'description' => ['required', 'max:500', 'string', 'min:10'],
            'block_level' => ['required', 'numeric', 'between:6,9']
        ], [
            'name.required' => 'Tên môn học là bắt buộc.',
            'name.max' => 'Tên môn học không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên môn học đã tồn tại, vui lòng chọn tên khác.',

            'description.required' => 'Mô tả là bắt buộc.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.min' => 'Mô tả phải có ít nhất 10 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',

            'block_level.required' => 'Khối là bắt buộc.',
            'block_level.numeric' => 'Khối phải là số nguyên.',
            'block_level.between' => 'Khối chỉ được nhập số từ 6 đến 9.'
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'block_level' => $request->block_level
        ];

        try {
            $subject = $this->subjectservice->update($data, $id);
            return $this->successResponse($subject, 'SUCCESS', Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->subjectservice->destroy($id);
            return $this->successResponse(null, "Xóa thành công môn học", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($id)
    {
        try {
            $subject = $this->subjectservice->backup($id);
            return $this->successResponse($subject, "Khôi phục thành công", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
