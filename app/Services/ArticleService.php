<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Classes;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleService
{
    public function createNewArticle(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::where('username', $data['username'])->first();

            if ($user === null) {
                throw new Exception('Không tìm thấy người dùng');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();
            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            if (isset($data['attachments'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'articles/' . Str::random(6) . $data['attachments']->getClientOriginalName();

                $storage->upload(
                    file_get_contents($data['attachments']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
            }

            $slug = time() . '-' . Str::slug($data['title']);
            $data['attachments'] = $firebasePath;

            $article = Article::create([
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'teacher_id' => $user->id,
                'class_id' => $class->id,
                'attachments' => $data['attachments'] ?? null,
            ]);

            return $article;
        });
    }

    public function updateArticle(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            $user = User::where('username', $data['username'])->first();

            if ($user === null) {
                throw new Exception('Không tìm thấy người dùng');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();
            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            if (isset($data['attachments'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'articles/' . $data['attachments']->getClientOriginalName();

                if ($article->attachments) {
                    $oldFirebasePath = $article->attachments;

                    $oldFile = $storage->object($oldFirebasePath);

                    if ($oldFile->exists()) {
                        $oldFile->delete();
                    }
                }

                $storage->upload(
                    file_get_contents($data['attachments']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
                $data['attachments'] = $firebasePath;
            }

            $article->update([
                'title' => $data['title'],
                'content' => $data['content'],
                'teacher_id' => $user->id,
                'class_id' => $class->id,
                'attachments' => $data['attachments'] ?? null,
            ]);

            return $article;
        });
    }

    public function deleteArticle($slug)
    {
        return DB::transaction(function () use ($slug) {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            $article->delete();

            return $article;
        });
    }

    public function restoreArticle($slug)
    {
        return DB::transaction(function () use ($slug) {
            $article = Article::onlyTrashed()->where('slug', $slug)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            $article->restore();

            return $article;
        });
    }

    public function forceDeleteArticle($slug)
    {
        return DB::transaction(function () use ($slug) {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                throw new Exception('Không tìm thấy bài viết');
            }

            $article->forceDelete();

            return $article;
        });
    }
}