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

    protected static function booted()
    {
        static::deleting(function ($academicYearClass) {
            if ($academicYearClass->class) {
                $academicYearClass->class()->delete();
            }

            if ($academicYearClass->academicYear) {
                $academicYearClass->academicYear()->delete();
            }
        });
    }
}
