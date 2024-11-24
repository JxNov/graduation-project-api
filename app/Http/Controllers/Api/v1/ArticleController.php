<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;

use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ArticleService;

use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class ArticleController extends Controller
{
    use ApiResponseTrait;

    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function index()
    {
        try {
            $articles = Article::select('content', 'teacher_id', 'published_at')
                ->with(['teacher'])
                ->paginate(10);

            if ($articles->isEmpty()) {
                return $this->errorResponse('Không có bài viết', Response::HTTP_BAD_REQUEST);
            }

            return $this->successResponse(
                new ArticleCollection($articles),
                'Lấy danh sách bài viết thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(ArticleRequest $request)
    {
        try {
            $data = $request->validated();
            $article = $this->articleService->createNewArticle($data['content']);

            return $this->successResponse(
                new ArticleResource($article),
                'Thêm mới bài viết thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($id)
    {
        try {
            $this->articleService->forceDeleteArticle($id);

            return $this->successResponse(
                null,
                'Xóa vĩnh viền bài viết thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}

