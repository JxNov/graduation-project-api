<?php
namespace App\Services;

use App\Models\Article;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function createNewArticle($content)
    {
        return DB::transaction(function () use ($content) {
            $user = Auth::user();

            if ($user === null) {
                throw new Exception('Không tìm thấy người dùng');
            }

            $article = Article::create([
                'content' => $content,
                'teacher_id' => $user->id,
                'published_at' => now()
            ]);

            return $article;
        });
    }

    public function forceDeleteArticle($id)
    {
        return DB::transaction(function () use ($id) {
            $article = Article::where('id', $id)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            $article->forceDelete();

            return $article;
        });
    }
}