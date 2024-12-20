<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'username' => 'admin',
                'date_of_birth' => '1999-01-01',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0123456789',
                'email' => 'admin@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Teacher 1',
                'username' => 'teacher1',
                'date_of_birth' => '1999-01-01',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0123456789',
                'email' => 'teacher1@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Teacher 2',
                'username' => 'teacher2',
                'date_of_birth' => '1999-01-01',
                'gender' => 'Female',
                'address' => 'Hà Nội',
                'phone_number' => '0123456789',
                'email' => 'teacher2@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Teacher 3',
                'username' => 'teacher3',
                'date_of_birth' => '1999-01-01',
                'gender' => 'Female',
                'address' => 'Hà Nội',
                'phone_number' => '0123456789',
                'email' => 'teacher3@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Nguyễn Văn Đoàn',
                'username' => 'doannvps33201',
                'date_of_birth' => '2004-09-19',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0333666999',
                'email' => 'doannvps33201@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Đỗ Minh Kiên',
                'username' => 'kiendmps32981',
                'date_of_birth' => '2004-11-08',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0333999666',
                'email' => 'kiendmps32981@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Nguyễn Mạnh Dũng',
                'username' => 'dungnmps30947',
                'date_of_birth' => '2003-11-30',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0999666666',
                'email' => 'dungnmps30947@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
            [
                'name' => 'Nguyễn Trung Hiếu',
                'username' => 'hieuntps31062',
                'date_of_birth' => '2001-07-15',
                'gender' => 'Male',
                'address' => 'Hà Nội',
                'phone_number' => '0666666999',
                'email' => 'hieuntps31062@tech4school.edu.vn',
                'password' => bcrypt(env('PASSWORD_DEFAULT')),
            ],
        ];

        foreach ($users as $item) {
            $user = User::create($item);

            if ($user->username === 'admin') {
                $user->roles()->attach(1);
            }

            if ($user->username === 'teacher1' || $user->username === 'teacher2' || $user->username === 'teacher3') {
                $user->roles()->attach(2);
            }

            if ($user->username === 'doannvps33201' || $user->username === 'kiendmps32981' || $user->username === 'dungnmps30947' || $user->username === 'hieuntps31062') {
                $user->roles()->attach(1);
            }
        }
    }
}
