<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Generation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
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
                if ($academicYear->classes()->exists()) {
                    $academicYear->delete();
                }

                $academicYear->delete();
            });
        });

        static::restoring(function ($generation) {
            $generation->academicYears()->withTrashed()->each(function ($academicYear) {
                if ($academicYear->classes()->exists()) {
                    $academicYear->restore();
                }
                $academicYear->restore();
            });
        });
    }
}
