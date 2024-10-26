<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Classes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'slug',
        'teacher_id',
        'academic_year_id',
    ];

    // giáo viên chủ nhiệm
    public function teacher()
    {
        return $this->hasOne(User::class, 'id', 'teacher_id');
    }

    public function academicYears()
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_year_classes', 'class_id', 'academic_year_id');
    }

    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_classes', 'class_id', 'block_id');
    }

    protected static function booted()
    {
        static::deleting(function ($class) {
            $blocks = $class->blocks;
            if ($blocks->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($blocks->pluck('id'), ['deleted_at' => now()]);
            }
            $class->blocks()->delete();

            $academicYears = $class->academicYears;
            if ($academicYears->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($academicYears->pluck('id'), ['deleted_at' => now()]);
            }
            $class->academicYears()->delete();
        });

        static::restoring(function ($class) {
            $blocks = $class->blocks;
            if ($blocks->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($blocks->pluck('id'), ['deleted_at' => null]);
            }
            $class->blocks()->restore();

            $academicYears = $class->academicYears;
            if ($academicYears->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($academicYears->pluck('id'), ['deleted_at' => null]);
            }
            $class->academicYears()->restore();
        });
    }
}
