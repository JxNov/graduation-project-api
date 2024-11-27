<?php
namespace App\Services;

use App\Events\CommentCreated;
use App\Models\Comment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

    public function updateComment($content, $id)
    {
        return DB::transaction(function () use ($content, $id) {
            $comment = Comment::where('id', $id)->first();
            
            if ($comment === null) {
                throw new Exception('Không tìm thấy bình luận');
            }
            
            Gate::authorize('update', $comment);

            $comment->content = $content;
            $comment->save();

            broadcast(new CommentCreated($comment))->toOthers();

            return $comment;
        });
    }
}
