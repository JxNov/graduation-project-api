<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;

use App\Http\Resources\ArticleResource;

use App\Services\ArticleService;

use App\Traits\ApiResponseTrait;
use Exception;

class ArticleController extends Controller
{
    use ApiResponseTrait;

    protected $articleservice;

    public function __construct(ArticleService $articleservice)
    {
        $this->articleservice = $articleservice;
    }
    public function store(ArticleRequest $request){
        try{
            $data = $request->validated();
           $post = $this->articleservice->createPost($data);
           return $this->successResponse( new ArticleResource($post),'Success');
        }
        catch(Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }
}

