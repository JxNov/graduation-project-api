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
            $generation->academicYears()->each(function ($academicYear) {
                foreach ($academicYear->classes as $class) {
                    $materials = $class->materials;

                    foreach ($materials as $material) {
                        $material->delete();
                    }

                    $blocks = $class->blocks;

                    foreach ($blocks as $block) {
                        $block->delete();
                    }
                }

                $academicYear->delete();
            });
        });

        static::restoring(function ($generation) {
            $academicYearTrash = $generation->academicYears()->withTrashed();

            $academicYearTrash->each(function ($academicYear) {
                $classes = $academicYear->classes()->withTrashed()->get();
                foreach ($classes as $class) {
                    $materials = $class->materials()->withTrashed()->get();
                    foreach ($materials as $material) {
                        $material->restore();
                    }

                    $blocks = $class->blocks()->withTrashed()->get();
                    foreach ($blocks as $block) {
                        $materialBlock = $block->classFromMaterials()->withTrashed()->get();
                        if ($materialBlock->isNotEmpty()) {
                            $block->classFromMaterials()->updateExistingPivot($materialBlock->pluck('id'), ['deleted_at' => null]);
                        }
                        $block->restore();
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
