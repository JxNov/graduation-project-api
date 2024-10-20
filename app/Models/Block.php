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
        'level'
    ];
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'block_subject');
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'block_classes', 'block_id', 'class_id');
    }

    protected static function booted()
    {
        static::deleting(function ($block) {
            $classes = $block->classes;
            if ($classes->isNotEmpty()) {
                $block->classes()->updateExistingPivot($classes->pluck('id'), ['deleted_at' => now()]);
            }
        });

        static::restoring(function ($block) {
            $classes = $block->classes;
            if ($classes->isNotEmpty()) {
                $block->classes()->updateExistingPivot($classes->pluck('id'), ['deleted_at' => null]);
            }
        });
    }
}
