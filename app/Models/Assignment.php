<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assignments';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'due_date',
        'criteria',
        'subject_id',
        'teacher_id',
        'class_id',
        'semester_id',
    ];

    /**
     * Relationships
     */

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function submittedAssignments()
    {
        return $this->hasMany(SubmittedAssignment::class, 'assignment_id');
    }

    protected static function booted()
    {
        static::deleting(function ($assignment) {
            foreach ($assignment->submittedAssignments as $submittedAssignment) {
                $submittedAssignment->delete();
            }

            $semester = $assignment->semester;

            $subjectScores = Score::where('semester_id', $semester->id)->get();
            $finalScores = FinalScore::where('semester_id', $semester->id)->get();

            foreach ($subjectScores as $subjectScore) {
                $subjectScore->delete();
            }

            foreach ($finalScores as $finalSore) {
                $finalSore->delete();
            }
        });

        static::restoring(function ($assignment) {
            $assignment->submittedAssignments()->withTrashed()->get()->each(function ($submitedAssignment) {
                $submitedAssignment->restore();
            });

            $semester = $assignment->semester;

            $subjectScores = Score::where('semester_id', $semester->id)->withTrashed()->get();
            $finalScores = FinalScore::where('semester_id', $semester->id)->withTrashed()->get();

            foreach ($subjectScores as $subjectScore) {
                $subjectScore->restore();
            }

            foreach ($finalScores as $finalSore) {
                $finalSore->restore();
            }
        });
    }
}
