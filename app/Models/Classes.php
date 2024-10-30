<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Classes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'teacher_id'
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

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'class_materials', 'class_id', 'material_id');
    }

    protected static function booted()
    {
        static::creating(function ($class) {
            do {
                $code = strtolower(Str::random(7));
            } while ($class::where('code', $code)->exists());

            $class->code = $code;
        });

        static::deleting(function ($class) {
            if ($class->blocks->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->academicYears->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($class->academicYears->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->materials->isNotEmpty()) {
                $class->materials()->updateExistingPivot($class->materials->pluck('id'), ['deleted_at' => now()]);
            }
        });

        static::restoring(function ($class) {
            $blockClass = $class->blocks()->withTrashed()->get();
            if ($blockClass->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($blockClass->pluck('id'), ['deleted_at' => null]);
            }

            $academicYearClass = $class->academicYears()->withTrashed()->get();
            if ($academicYearClass->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($academicYearClass->pluck('id'), ['deleted_at' => null]);
            }

            $materialClass = $class->materials()->withTrashed()->get();
            if ($materialClass->isNotEmpty()) {
                $class->materials()->updateExistingPivot($materialClass->pluck('id'), ['deleted_at' => null]);
            }
        });
    }
}
