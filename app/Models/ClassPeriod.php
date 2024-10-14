<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassPeriod extends Model
{
    use HasFactory;

    const _CLASS_PERIOD = [
        'Tiết 1' => 'Tiết 1',
        'Tiết 2' => 'Tiết 2',
        'Tiết 3' => 'Tiết 3',
        'Tiết 4' => 'Tiết 4',
        'Tiết 5' => 'Tiết 5',
    ];

    const _TIME_CLASS_PERIOD_AM = [
        'Tiết 1' => [
            'start_time' => '07:15',
            'end_time' => '08:00'
        ],
        'Tiết 2' => [
            'start_time' => '08:05',
            'end_time' => '08:50'
        ],
        'Tiết 3' => [
            'start_time' => '09:00',
            'end_time' => '09:45'
        ],
        'Tiết 4' => [
            'start_time' => '09:50',
            'end_time' => '10:35'
        ],
        'Tiết 5' => [
            'start_time' => '10:40',
            'end_time' => '11:25'
        ],
    ];

    const _TIME_CLASS_PERIOD_PM = [
        'Tiết 1' => [
            'start_time' => '12:50',
            'end_time' => '13:35'
        ],
        'Tiết 2' => [
            'start_time' => '13:40',
            'end_time' => '14:25'
        ],
        'Tiết 3' => [
            'start_time' => '14:35',
            'end_time' => '15:20'
        ],
        'Tiết 4' => [
            'start_time' => '15:25',
            'end_time' => '16:10'
        ],
        'Tiết 5' => [
            'start_time' => '16:15',
            'end_time' => '17:00'
        ],
    ];
}
