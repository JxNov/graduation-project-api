<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'id',
        'slug',
        'name',
        'description',
        'block_level'
    ];
    protected $table = 'subjects';
    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_subject');
    }
    public function classes()
{
    return $this->belongsToMany(Classes::class, 'subject_classes', 'subject_id', 'class_id');
}

protected static function booted()
    {
        
        static::deleting(function ($subject) {
            if ($subject->blocks->isNotEmpty()) {
                $subject->blocks()->updateExistingPivot($subject->blocks->pluck('id'), ['deleted_at' => now()]);
            }
            if ($subject->classes->isNotEmpty()) {
                $subject->classes()->updateExistingPivot($subject->classes->pluck('id'), ['deleted_at' => now()]);
            }

        });

        static::restoring(function ($subject) {
            $blocksubject = $subject->blocks()->withTrashed()->get();
            if ($blocksubject->isNotEmpty()) {
                $subject->blocks()->updateExistingPivot($blocksubject->pluck('id'), ['deleted_at' => null]);
            }
            $subjectclass = $subject->classes()->withTrashed()->get();
            if ($subjectclass->isNotEmpty()) {
                $subject->classes()->updateExistingPivot($subjectclass->pluck('id'), ['deleted_at' => null]);
            }
        });
    }
}
