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

    public static function booted()
    {
        static::deleting(function ($material) {
            if ($material->classes->isNotEmpty()) {
                $material->classes()->updateExistingPivot($material->classes->pluck('id'), ['deleted_at' => now()]);
            }

            if ($material->blocks->isNotEmpty()) {
                $material->blocks()->updateExistingPivot($material->blocks->pluck('id'), ['deleted_at' => now()]);
            }
        });

        static::restoring(function ($material) {
            $materialClassTrashed = $material->classes()->withTrashed()->get();
            $materialBlockTrashed = $material->blocks()->withTrashed()->get();

            if ($materialClassTrashed->isNotEmpty()) {
                $material->classes()->updateExistingPivot($material->classes->pluck('id'), ['deleted_at' => null]);
            }

            if ($materialBlockTrashed->isNotEmpty()) {
                $material->blocks()->updateExistingPivot($material->blocks->pluck('id'), ['deleted_at' => null]);
            }
        });
    }
}
