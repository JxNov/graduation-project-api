<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYearClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'class_id',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }

    public static function booted()
    {
        static::deleting(function ($academicYearClass) {
            $academicYearClass->academicYear()->delete();
            $academicYearClass->class()->delete();
        });

        static::restoring(function ($academicYearClass) {
            $academicYearClass->academicYear()->withTrashed()->restore();
            $academicYearClass->class()->withTrashed()->restore();
        });
    }
}
