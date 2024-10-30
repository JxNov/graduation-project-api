<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
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

    public function store(SubjectRequest $request)
    {
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

    public function update(SubjectRequest $request, $slug)
    {

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'block_level' => $request->block_level
        ];

        try {
            $subject = $this->subjectservice->update($data, $slug);
            return $this->successResponse($subject, 'SUCCESS', Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->subjectservice->destroy($slug);
            return $this->successResponse(null, "Xóa thành công môn học", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($slug)
    {
        try {
            $subject = $this->subjectservice->backup($slug);
            return $this->successResponse($subject, "Khôi phục thành công", Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
