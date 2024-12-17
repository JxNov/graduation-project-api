<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Block;

class BlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $blocks = [
            [
                'name' => 'Khối 6',
                'slug' => 'khoi-6',
            ],
            [
                'name' => 'Khối 7',
                'slug' => 'khoi-7',
            ],
            [
                'name' => 'Khối 8',
                'slug' => 'khoi-8',
            ],
            [
                'name' => 'Khối 9',
                'slug' => 'khoi-9',
            ],
        ];

        foreach ($blocks as $item) {
            Block::create($item);
        }
    }
}
