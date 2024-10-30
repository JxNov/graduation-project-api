<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'block_classes';

    protected $fillable = [
        'block_id',
        'class_id',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }
}
