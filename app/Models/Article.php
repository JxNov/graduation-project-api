<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content',
        'teacher_id',
        'published_at',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public static function booted()
    {
        static::deleting(function ($article) {
            $article->comments->each(function ($comment) {
                $comment->delete();
            });
        });

        static::restoring(function ($article) {
            $article->comments()->onlyTrashed()->each(function ($comment) {
                $comment->restore();
            });
        });
    }
}
