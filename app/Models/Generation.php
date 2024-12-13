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

    protected static function booted()
    {
        static::deleting(function ($generation) {
            if ($generation->users->isNotEmpty()) {
                $generation->users()->updateExistingPivot($generation->users->pluck('id'), ['deleted_at' => now()]);
            }

            $generation->academicYears()->each(function ($academicYear) {
                foreach ($academicYear->classes as $class) {
                    $materials = $class->materials;

                    foreach ($materials as $material) {
                        $material->delete();
                    }

                    $classTeachers = $class->classTeachers;

                    if ($classTeachers->isNotEmpty()) {
                        $class->classTeachers()->updateExistingPivot($classTeachers->pluck('id'), ['deleted_at' => now()]);
                    }
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
                    $materials = $class->materials()->withTrashed()->get();
                    foreach ($materials as $material) {
                        $material->restore();
                    }

                    $classTeachers = $class->classTeachers()->withTrashed()->get();

                    if ($classTeachers->isNotEmpty()) {
                        $class->classTeachers()->updateExistingPivot($classTeachers->pluck('id'), ['deleted_at' => null]);
                    }
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
