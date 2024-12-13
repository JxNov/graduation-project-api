<?php

namespace Database\Seeders;

use App\Models\ClassPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = [
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

        foreach ($lessons as $key => $value) {
            ClassPeriod::create([
                'lesson' => $key,
                'start_time' => $value['start_time'],
                'end_time' => $value['end_time'],
            ]);
        }
    }
}
