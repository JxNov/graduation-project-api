<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    const _SHIFTS = [
        'Morning' => 'Morning',
        'Afternoon' => 'Afternoon'
    ];

    protected $fillable = [
        'date',
        'shifts',
        'reason',
        'class_id',
    ];
}
