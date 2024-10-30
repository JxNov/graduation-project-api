<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'block_id'
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }
}
