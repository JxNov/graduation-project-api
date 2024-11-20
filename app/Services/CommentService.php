<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;


class CommentService
{
    public function addComment($postId, $content)
    {
        // Lấy vai trò 'student'
        $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();

        // Tìm người dùng có username và vai trò 'student'
        $user = User::where('username', $content['username'])
            ->whereHas('roles', function ($query) use ($roleStudent) {
                $query->where('role_id', $roleStudent->id);
            })->first();
        if (!$user) {
            throw new Exception('Username này không phải là học sinh');
        }
        $post = Article::findOrFail($postId);

        
        return $post->comments()->create([
            'user_id' => $user->id, 
            'content' => $content['content'],
        ]);
    }
}