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
            $articles = Article::select('title', 'content', 'attachments', 'teacher_id', 'class_id')
                ->with(['teacher', 'class'])
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
            $article = $this->articleService->createNewArticle($data);

            return $this->successResponse(
                new ArticleResource($article),
                'Thêm mới bài viết thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($slug)
    {
        try {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            return $this->successResponse(
                new ArticleResource($article),
                'Lấy bài viết thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(ArticleRequest $request, $slug)
    {
        try {
            $data = $request->validated();
            $article = $this->articleService->updateArticle($data, $slug);

            return $this->successResponse(
                new ArticleResource($article),
                'Sửa bài viết thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->articleService->deleteArticle($slug);

            return $this->successResponse(
                null,
                'Xóa bài viết thành công',
                Response::HTTP_NO_CONTENT
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function trash()
    {
        try {
            $articles = Article::select('title', 'content', 'attachments', 'teacher_id', 'class_id')
                ->with(['teacher', 'class'])
                ->onlyTrashed()
                ->paginate(10);

            if ($articles->isEmpty()) {
                return $this->errorResponse('Không có bài viết', Response::HTTP_BAD_REQUEST);
            }

            return $this->successResponse(
                new ArticleCollection($articles),
                'Lấy danh sách bài viết đã xóa thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore($slug)
    {
        try {
            $article = $this->articleService->restoreArticle($slug);

            return $this->successResponse(
                new ArticleResource($article),
                'Khôi phục bài viết thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function forceDelete($slug)
    {
        try {
            $this->articleService->forceDeleteArticle($slug);

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

