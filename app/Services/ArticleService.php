<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Classes;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function createNewArticle(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();

            if ($user === null) {
                throw new Exception('Không tìm thấy người dùng');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $article = Article::create([
                'content' => $data['content'],
                'teacher_id' => $user->id,
                'class_id' => $class->id,
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