<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'generation_id',
    ];

    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'academic_year_classes', 'academic_year_id', 'class_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_generations');
    }

    protected static function booted()
    {
        static::deleting(function ($academicYear) {
            $academicYear->semesters()->each(function ($semester) {
                $semester->delete();
            });

            if ($academicYear->classes->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($academicYear->classes->pluck('id'), ['deleted_at' => now()]);
            }

            $academicYear->classes()->each(function ($class) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => now()]);
            });

            $academicYear->classes()->delete();

        });

        static::restoring(function ($academicYear) {
            $semesterTrash = $academicYear->semesters()->withTrashed();

            $semesterTrash->each(function ($semester) {
                $semester->restore();
            });

            $academicYearClass = $academicYear->classes()->withTrashed()->get();
            if ($academicYearClass->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($academicYearClass->pluck('id'), ['deleted_at' => null]);
            }

            $classTrash = $academicYear->classes()->withTrashed();
            $classTrash->each(function ($class) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => null]);

                $class->restore();
            });

        });
    }
}
