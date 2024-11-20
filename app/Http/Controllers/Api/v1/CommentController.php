<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use ApiResponseTrait;

    protected $commentservice;

    public function __construct(CommentService $commentservice)
    {
        $this->commentservice = $commentservice;
    }
    public function store(CommentRequest $request,$postId){
        try{
            $content = $request->validated();
           $comments = $this->commentservice->addComment($postId,$content);
           return $this->successResponse( new CommentResource($comments),'Success');
        }
        catch(Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }
}
