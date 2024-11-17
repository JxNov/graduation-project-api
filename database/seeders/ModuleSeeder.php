<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'admin',
                'title' => 'Quản trị viên'
            ],
            [
                'name' => 'teacher',
                'title' => 'Giáo viên'
            ],
            [
                'name' => 'student',
                'title' => 'Học sinh'
            ],
            [
                'name' => 'parent',
                'title' => 'Phụ huynh'
            ],
        ];

        foreach ($modules as $item) {
            Module::create($item);
        }
    }
}
