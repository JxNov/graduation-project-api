<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =[
        'id','name','description','block_level'
    ];

    protected $table = 'subjects';

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'block_subject');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
