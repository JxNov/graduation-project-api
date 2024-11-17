<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'name' => 'Ngữ văn',
                'slug' => 'ngu-van',
            ],
            [
                'name' => 'Toán',
                'slug' => 'toan',
            ],
            [
                'name' => 'Tiếng Anh',
                'slug' => 'tieng-anh',
            ],
            [
                'name' => 'GDCD',
                'slug' => 'gdcd',
            ],
            [
                'name' => 'Lịch sử',
                'slug' => 'lich-su',
            ],
            [
                'name' => 'Địa lý',
                'slug' => 'dia-ly',
            ],
            [
                'name' => 'Vật lý',
                'slug' => 'vat-ly',
            ],
            [
                'name' => 'Công nghệ',
                'slug' => 'cong-nghe',
            ],
            [
                'name' => 'Hóa học',
                'slug' => 'hoa-hoc',
            ],
            [
                'name' => 'Sinh học',
                'slug' => 'sinh-hoc',
            ],
            [
                'name' => 'Tin học',
                'slug' => 'tin-hoc',
            ],
            [
                'name' => 'Thể dục',
                'slug' => 'the-duc',
            ],
            [
                'name' => 'Âm nhạc',
                'slug' => 'am-nhac',
            ],
            [
                'name' => 'Mỹ thuật',
                'slug' => 'my-thuat',
            ],
        ];

        foreach ($subjects as $item) {
            Subject::create($item);
        }
    }
}
