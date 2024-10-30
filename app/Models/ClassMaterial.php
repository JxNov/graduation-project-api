<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'class_id'
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }
}
