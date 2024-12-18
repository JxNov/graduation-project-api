<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_path',
        'subject_id',
        'teacher_id'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_materials', 'material_id', 'block_id');
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_materials', 'material_id', 'class_id');
    }

    // protected static function booted()
    // {
    //     static::deleting(function ($material) {
    //         if ($material->block->isNotEmpty()) {
    //             $material->block()->updateExistingPivot($material->block->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($material->classes->isNotEmpty()) {
    //             $material->classes()->updateExistingPivot($material->classes->pluck('id'), ['deleted_at' => now()]);
    //         }
    //     });

    //     static::restoring(function ($material) {
    //         $blockMaterial = $material->blocks()->withTrashed()->get();
    //         if ($blockMaterial->isNotEmpty()) {
    //             $material->blocks()->updateExistingPivot($blockMaterial->pluck('id'), ['deleted_at' => null]);
    //         }
    //         $classMaterial = $material->classes()->withTrashed()->get();
    //         if ($classMaterial->isNotEmpty()) {
    //             $material->classes()->updateExistingPivot($classMaterial->pluck('id'), ['deleted_at' => null]);
    //         }
    //     });
    // }
}
