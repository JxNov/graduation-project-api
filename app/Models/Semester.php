<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'academic_year_id',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function finalScores()
    {
        return $this->hasMany(FinalScore::class, 'semester_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'semester_id');
    }

    public function subjectScores()
    {
        return $this->hasMany(Score::class, 'semester_id');
    }

    protected static function booted()
    {
        static::deleting(function ($semester) {
            foreach ($semester->assignments as $assignment) {
                foreach ($assignment->submittedAssignments as $submittedAssignment) {
                    $submittedAssignment->delete();
                }

                $assignment->delete();
            }

            foreach ($semester->subjectScores as $subjectScore) {
                $subjectScore->delete();
            }

            foreach ($semester->finalScores as $finalScore) {
                $finalScore->delete();
            }
        });

        static::restoring(function ($semester) {
            foreach ($semester->assignments()->withTrashed()->get() as $assignment) {
                foreach ($assignment->submittedAssignments()->withTrashed()->get() as $submittedAssignment) {
                    $submittedAssignment->restore();
                }

                $assignment->restore();
            }

            foreach ($semester->subjectScores()->withTrashed()->get() as $subjectScore) {
                $subjectScore->restore();
            }

            foreach ($semester->finalScores()->withTrashed()->get() as $finalScore) {
                $finalScore->restore();
            }
        });
    }
}
