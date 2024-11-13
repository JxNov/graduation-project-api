<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'block_subjects');
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'block_classes', 'block_id', 'class_id');
    }

    public function classFromMaterials()
    {
        return $this->belongsToMany(Material::class, 'block_materials', 'block_id', 'material_id');
    }

    protected static function booted()
    {
        static::deleting(function ($block) {
            $classes = $block->classes;
            if ($classes->isNotEmpty()) {
                $block->classes()->updateExistingPivot($classes->pluck('id'), ['deleted_at' => now()]);
            }

            $classFromMaterials = $block->classFromMaterials;
            if ($classFromMaterials->isNotEmpty()) {
                $block->classFromMaterials()->updateExistingPivot($classFromMaterials->pluck('id'), ['deleted_at' => now()]);
            }

            $block->classes()->each(function ($class) {
                $class->academicYears()->updateExistingPivot($class->academicYears->pluck('id'), ['deleted_at' => now()]);
            });

            $block->classes()->delete();
        });

        static::restoring(function ($block) {
            $blockClass = $block->classes()->withTrashed()->get();
            if ($blockClass->isNotEmpty()) {
                $block->classes()->updateExistingPivot($blockClass->pluck('id'), ['deleted_at' => null]);
            }

            $blockMaterial = $block->classFromMaterials()->withTrashed()->get();
            if ($blockMaterial->isNotEmpty()) {
                $block->classFromMaterials()->updateExistingPivot($blockMaterial->pluck('id'), ['deleted_at' => null]);
            }

            $classTrash = $block->classes()->withTrashed();
            $classTrash->each(function ($class) {
                $class->academicYears()->updateExistingPivot($class->academicYears->pluck('id'), ['deleted_at' => null]);

                $class->restore();
            });
        });
    }
}
