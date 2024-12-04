<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceDetail extends Model
{
    use HasFactory, SoftDeletes;

    const _STATUS = [
        'Present' => 'Present',
        'Absent' => 'Absent',
        'Late' => 'Late',
        'Excused' => 'Excused',
        'Medical Leave' => 'Medical Leave',
        'Other' => 'Other',
    ];

    protected $fillable = [
        'attendance_id',
        'student_id',
        'status',
        'reason',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
