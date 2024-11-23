<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'attachments',
        'teacher_id',
        'class_id'
    ];
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
