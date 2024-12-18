<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'final_scores';
    
    protected $fillable = [
        'average_score',
        'student_id',
        'academic_year_id',
        'semester_id',
        'class_id',
        'performance_level'
    ];
}
