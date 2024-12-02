<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    use ApiResponseTrait;

    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function store(CommentRequest $request)
    {
        try {
            $data = $request->validated();
            $comment = $this->commentService->addComment($data);
            return $this->successResponse(
                new CommentResource($comment),
                'Bình luận thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(CommentRequest $request, $id)
    {
        try {
            $content = $request->validated();

            $comment = $this->commentService->updateComment($content, $id);

            return $this->successResponse(
                new CommentResource($comment),
                'Sửa bình luận thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
