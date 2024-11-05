<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectClasses extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subject_classes';

    protected $fillable = [
        'class_id',
        'subject_id',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // Định nghĩa mối quan hệ với model Subject
    public function subjects()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
