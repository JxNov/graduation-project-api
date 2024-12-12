<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    public function forceDelete(User $user, Article $article): bool
    {
        return $user->isAdmin() || $user->id === $article->teacher_id;
    }
}
