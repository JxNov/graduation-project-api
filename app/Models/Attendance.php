<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    const _STATUS = [
        'Present' => 'Present',
        'Absent' => 'Absent',
        'Late' => 'Late',
        'Excused' => 'Excused',
        'Medical Leave' => 'Medical Leave'
    ];
}
