<?php
namespace App\Services;

use App\Events\CommentCreated;
use App\Models\Article;
use App\Models\Comment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function addComment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();

            $comment = Comment::create([
                'article_id' => $data['article_id'],
                'user_id' => $user->id,
                'content' => $data['content'],
            ]);

            broadcast(new CommentCreated($comment))->toOthers();

            return $comment;
        });
    }
}
