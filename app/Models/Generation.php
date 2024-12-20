<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function students()
    {
        return $this->belongsToMany(User::class, 'user_generations');
    }

    protected static function booted()
    {
        static::deleting(function ($generation) {
            if ($generation->users->isNotEmpty()) {
                $generation->users()->updateExistingPivot($generation->users->pluck('id'), ['deleted_at' => now()]);
            }

            $generation->academicYears()->each(function ($academicYear) {
                foreach ($academicYear->classes as $class) {
                    $classTeachers = $class->classTeachers;
                    $students = $class->students;
                    $materials = $class->materials;

                    if ($classTeachers->isNotEmpty()) {
                        $class->classTeachers()->updateExistingPivot($classTeachers->pluck('id'), ['deleted_at' => now()]);
                    }

                    if ($students->isNotEmpty()) {
                        $class->students()->updateExistingPivot($students->pluck('id'), ['deleted_at' => now()]);
                    }

                    if ($materials->isNotEmpty()) {
                        $class->materials()->updateExistingPivot($materials->pluck('id'), ['deleted_at' => now()]);
                    }

                    $classAttendances = $class->attendances;
                    $classAssignment = $class->assignments;
                    $subjects = $class->subjects;
                    $subjectScores = $class->subjectScores;
                    $schedules = $class->schedules;
                    $articles = $class->articles;

                    foreach ($classAttendances as $attendance) {
                        foreach ($attendance->attendanceDetails as $attendanceDetail) {
                            $attendanceDetail->delete();
                        }
                        $attendance->delete();
                    }

                    foreach ($articles as $article) {
                        foreach ($article->comments as $comments) {
                            $comments->delete();
                        }
                        $article->delete();
                    }

                    foreach ($classAssignment as $assignment) {
                        foreach ($assignment->submittedAssignments as $submittedAssignment) {
                            $submittedAssignment->delete();
                        }
                        $assignment->delete();
                    }

                    if ($subjects->isNotEmpty()) {
                        $class->subjects()->updateExistingPivot($subjects->pluck('id'), ['deleted_at' => now()]);
                    }

                    foreach ($subjectScores as $subjectScore) {
                        $subjectScore->delete();
                    }

                    foreach ($schedules as $schedule) {
                        $schedule->delete();
                    }
                }

                foreach ($academicYear->finalScores as $finalScore) {
                    $finalScore->delete();
                }

                $academicYear->delete();
            });
        });

        static::restoring(function ($generation) {
            $userGeneration = $generation->users()->withTrashed()->get();
            if ($userGeneration->isNotEmpty()) {
                $generation->users()->updateExistingPivot($userGeneration->pluck('id'), ['deleted_at' => null]);
            }

            $academicYearTrash = $generation->academicYears()->withTrashed();

            $academicYearTrash->each(function ($academicYear) {
                $classes = $academicYear->classes()->withTrashed()->get();
                foreach ($classes as $class) {
                    $classTeachers = $class->classTeachers()->withTrashed()->get();
                    $students = $class->students()->withTrashed()->get();
                    $materials = $class->materials()->withTrashed()->get();

                    if ($classTeachers->isNotEmpty()) {
                        $class->classTeachers()->updateExistingPivot($classTeachers->pluck('id'), ['deleted_at' => null]);
                    }

                    if ($students->isNotEmpty()) {
                        $class->students()->updateExistingPivot($students->pluck('id'), ['deleted_at' => null]);
                    }

                    if ($materials->isNotEmpty()) {
                        $class->materials()->updateExistingPivot($materials->pluck('id'), ['deleted_at' => null]);
                    }

                    $classAttendances = $class->attendances()->withTrashed()->get();
                    $classAssignment = $class->assignments()->withTrashed()->get();
                    $subjects = $class->subjects()->withTrashed()->get();
                    $subjectScores = $class->subjectScores()->withTrashed()->get();
                    $schedules = $class->schedules()->withTrashed()->get();
                    $articles = $class->articles()->withTrashed()->get();

                    foreach ($classAttendances as $attendance) {
                        $attendance->restore();
                        foreach ($attendance->attendanceDetails()->withTrashed()->get() as $attendanceDetail) {
                            $attendanceDetail->restore();
                        }
                    }

                    foreach ($articles as $article) {
                        foreach ($article->comments()->withTrashed()->get() as $comments) {
                            $comments->delete();
                        }
                        $article->delete();
                    }

                    foreach ($classAssignment as $assignment) {
                        $assignment->restore();
                        foreach ($assignment->submittedAssignments()->withTrashed()->get() as $submittedAssignment) {
                            $submittedAssignment->restore();
                        }
                    }

                    if ($subjects->isNotEmpty()) {
                        $class->subjects()->updateExistingPivot($subjects->pluck('id'), ['deleted_at' => null]);
                    }

                    foreach ($subjectScores as $subjectScore) {
                        $subjectScore->restore();
                    }

                    foreach ($schedules as $schedule) {
                        $schedule->restore();
                    }
                }

                foreach ($academicYear->finalScores()->withTrashed()->get() as $finalScore) {
                    $finalScore->restore();
                }

                $academicYear->restore();
            });
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_generations');
    }
}
