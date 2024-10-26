<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected static function booted()
    {
        static::deleting(function ($academicYear) {
            $academicYear->semesters()->each(function ($semester) {
                $semester->delete();
            });

            $classes = $academicYear->classes;
            if ($classes->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($classes->pluck('id'), ['deleted_at' => now()]);
            }

            $academicYear->classes()->delete();
        });

        static::restoring(function ($academicYear) {
            $academicYear->semesters()->withTrashed()->each(function ($semester) {
                $semester->restore();
            });

            $classes = $academicYear->classes;
            if ($classes->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($classes->pluck('id'), ['deleted_at' => null]);
            }

            $academicYear->classes()->restore();
        });
    }
}
