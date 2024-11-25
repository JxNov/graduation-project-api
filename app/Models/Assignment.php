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
        return $this->hasMany(SubmittedAssignment::class);
    }
}
