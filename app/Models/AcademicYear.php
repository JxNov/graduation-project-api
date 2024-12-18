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

    public function finalScores()
    {
        return $this->hasMany(FinalScore::class, 'academic_year_id');
    }

    protected static function booted()
    {
        static::deleting(function ($academicYear) {
            $academicYear->semesters()->each(function ($semester) {
                $semester->delete();
            });

            if ($academicYear->users->isNotEmpty()) {
                $academicYear->users()->updateExistingPivot($academicYear->users->pluck('id'), ['deleted_at' => now()]);
            }

            if ($academicYear->classes->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($academicYear->classes->pluck('id'), ['deleted_at' => now()]);
            }

            foreach ($academicYear->finalScores as $finalScore) {
                $finalScore->delete();
            }

            $academicYear->classes()->each(function ($class) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => now()]);
                $class->materials()->updateExistingPivot($class->materials->pluck('id'), ['deleted_at' => now()]);
                $class->students()->updateExistingPivot($class->students->pluck('id'), ['deleted_at' => now()]);
                $class->classTeachers()->updateExistingPivot($class->classTeachers->pluck('id'), ['deleted_at' => now()]);

                foreach ($class->assignments as $assignment) {
                    foreach ($assignment->submittedAssignments as $submittedAssignment) {
                        $submittedAssignment->delete();
                    }

                    $assignment->delete();
                }

                foreach ($class->articles as $article) {
                    foreach ($article->comments as $comments) {
                        $comments->delete();
                    }
                    $article->delete();
                }

                foreach ($class->attendances as $attendance) {
                    foreach ($attendance->attendanceDetails as $attendanceDetail) {
                        $attendanceDetail->delete();
                    }

                    $attendance->delete();
                }

                if ($class->subjects->isNotEmpty()) {
                    $class->subjects()->updateExistingPivot($class->subjects->pluck('id'), ['deleted_at' => now()]);
                }

                foreach ($class->subjectScores as $subjectScore) {
                    $subjectScore->delete();
                }

                foreach ($class->schedules as $schedule) {
                    $schedule->delete();
                }
            });

            $academicYear->classes()->delete();

        });

        static::restoring(function ($academicYear) {
            $semesterTrash = $academicYear->semesters()->withTrashed();

            $semesterTrash->each(function ($semester) {
                $semester->restore();
            });

            $userClass = $academicYear->users()->withTrashed()->get();
            if ($userClass->isNotEmpty()) {
                $academicYear->users()->updateExistingPivot($userClass->pluck('id'), ['deleted_at' => null]);
            }

            $academicYearClass = $academicYear->classes()->withTrashed()->get();
            if ($academicYearClass->isNotEmpty()) {
                $academicYear->classes()->updateExistingPivot($academicYearClass->pluck('id'), ['deleted_at' => null]);
            }

            foreach ($academicYear->finalScores()->withTrashed()->get() as $finalScore) {
                $finalScore->restore();
            }

            $classTrash = $academicYear->classes()->withTrashed()->get();
            $classTrash->each(function ($class) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => null]);
                $class->materials()->updateExistingPivot($class->materials->pluck('id'), ['deleted_at' => null]);
                $class->students()->updateExistingPivot($class->students->pluck('id'), ['deleted_at' => null]);
                $class->classTeachers()->updateExistingPivot($class->classTeachers->pluck('id'), ['deleted_at' => null]);
                $class->subjects()->updateExistingPivot($class->subjects->pluck('id'), ['deleted_at' => null]);

                foreach ($class->assignments()->withTrashed()->get() as $assignment) {
                    foreach ($assignment->submittedAssignments()->withTrashed()->get() as $submittedAssignment) {
                        $submittedAssignment->restore();
                    }

                    $assignment->restore();
                }

                foreach ($class->attendances()->withTrashed()->get() as $attendance) {
                    foreach ($attendance->attendanceDetails()->withTrashed()->get() as $attendanceDetail) {
                        $attendanceDetail->restore();
                    }

                    $attendance->restore();
                }

                foreach ($class->articles()->withTrashed()->get() as $article) {
                    foreach ($article->comments()->withTrashed()->get() as $comments) {
                        $comments->delete();
                    }
                    $article->delete();
                }

                foreach ($class->subjectScores()->withTrashed()->get() as $subjectScore) {
                    $subjectScore->restore();
                }

                foreach ($class->schedules()->withTrashed()->get() as $schedule) {
                    $schedule->restore();
                }

                $class->restore();
            });
        });
    }
}
