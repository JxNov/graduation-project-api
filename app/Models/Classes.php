<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Classes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'teacher_id'
    ];

    // giáo viên chủ nhiệm
    public function teacher(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'teacher_id');
    }

    // giáo viên bộ môn
    public function classTeachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_teachers', 'class_id', 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'student_id');
    }

    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_year_classes', 'class_id', 'academic_year_id');
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'block_classes', 'class_id', 'block_id');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_classes', 'class_id', 'subject_id');
    }

    public function subjectScores()
    {
        return $this->hasMany(Score::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'class_materials', 'class_id', 'material_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'class_id');
    }

    public function finalScores()
    {
        return $this->hasMany(FinalScore::class, 'class_id');
    }

    protected static function booted()
    {
        static::creating(function ($class) {
            do {
                $code = strtolower(Str::random(7));
            } while ($class::where('code', $code)->exists());

            $class->code = $code;
        });

        static::deleting(function ($class) {
            if ($class->classTeachers->isNotEmpty()) {
                $class->classTeachers()->updateExistingPivot($class->classTeachers->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->students->isNotEmpty()) {
                $class->students()->updateExistingPivot($class->students->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->subjects->isNotEmpty()) {
                $class->subjects()->updateExistingPivot($class->subjects->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->blocks->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($class->blocks->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->academicYears->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($class->academicYears->pluck('id'), ['deleted_at' => now()]);
            }

            if ($class->materials->isNotEmpty()) {
                $class->materials()->updateExistingPivot($class->materials->pluck('id'), ['deleted_at' => now()]);
            }

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

            foreach ($class->subjectScores as $subjectScore) {
                $subjectScore->delete();
            }

            foreach ($class->schedules as $schedule) {
                $schedule->delete();
            }
        });

        static::restoring(function ($class) {
            $blockClass = $class->blocks()->withTrashed()->get();
            if ($blockClass->isNotEmpty()) {
                $class->blocks()->updateExistingPivot($blockClass->pluck('id'), ['deleted_at' => null]);
            }

            $studentClass = $class->students()->withTrashed()->get();
            if ($studentClass->isNotEmpty()) {
                $class->students()->updateExistingPivot($studentClass->pluck('id'), ['deleted_at' => null]);
            }

            $subjectClass = $class->subjects()->withTrashed()->get();
            if ($subjectClass->isNotEmpty()) {
                $class->subjects()->updateExistingPivot($subjectClass->pluck('id'), ['deleted_at' => null]);
            }

            $academicYearClass = $class->academicYears()->withTrashed()->get();
            if ($academicYearClass->isNotEmpty()) {
                $class->academicYears()->updateExistingPivot($academicYearClass->pluck('id'), ['deleted_at' => null]);
            }

            $materialClass = $class->materials()->withTrashed()->get();
            if ($materialClass->isNotEmpty()) {
                $class->materials()->updateExistingPivot($materialClass->pluck('id'), ['deleted_at' => null]);
            }

            $teachClass = $class->classTeachers()->withTrashed()->get();
            if ($teachClass->isNotEmpty()) {
                $class->classTeachers()->updateExistingPivot($teachClass->pluck('id'), ['deleted_at' => null]);
            }

            foreach ($class->assignments()->withTrashed()->get() as $assignment) {
                foreach ($assignment->submittedAssignments()->withTrashed()->get() as $submittedAssignment) {
                    $submittedAssignment->restore();
                }

                $assignment->restore();
            }

            foreach ($class->articles()->withTrashed()->get() as $article) {
                foreach ($article->comments()->withTrashed()->get() as $comments) {
                    $comments->restore();
                }
                $article->restore();
            }

            foreach ($class->attendances()->withTrashed()->get() as $attendance) {
                foreach ($attendance->attendanceDetails()->withTrashed()->get() as $attendanceDetail) {
                    $attendanceDetail->restore();
                }

                $attendance->restore();
            }

            foreach ($class->subjectScores()->withTrashed()->get() as $subjectScore) {
                $subjectScore->restore();
            }

            foreach ($class->schedules()->withTrashed()->get() as $schedule) {
                $schedule->restore();
            }
        });
    }
}
