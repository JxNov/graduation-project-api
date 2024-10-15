<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Generation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'year',
        'start_date',
        'end_date',
    ];

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    protected static function booted()
    {
        static::deleting(function ($generation) {
            $generation->academicYears()->each(function ($academicYear) {
                $academicYear->semesters()->each(function ($semester) {
                    $semester->delete();
                });
                $academicYear->delete();
            });
        });

        static::restoring(function ($generation) {
            $generation->academicYears()->withTrashed()->each(function ($academicYear) {
                $academicYear->restore();

                $academicYear->semesters()->withTrashed()->each(function ($semester) {
                    $semester->restore();
                });
            });
        });
    }
}
