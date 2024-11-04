<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'id',
        'slug',
        'name',
        'description',
        'block_level'
    ];
    protected $table = 'subjects';
    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_subject');
    }
    public function classes()
{
    return $this->belongsToMany(Classes::class, 'subject_classes', 'subject_id', 'class_id');
}

}
