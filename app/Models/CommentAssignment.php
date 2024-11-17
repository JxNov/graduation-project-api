<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommentAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'comment',
    ];
}
