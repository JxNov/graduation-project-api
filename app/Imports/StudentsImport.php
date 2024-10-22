<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $data = [];
        $existingUsernames = User::pluck('username')->toArray();

        foreach ($rows as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $name = isset($row[0]) ? trim($row[0]) : '';
            $dateOfBirth = isset($row[1]) ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1]))->format('Y-m-d') : null;
            $gender = isset($row[2]) ? trim($row[2]) : '';
            $address = isset($row[3]) ? trim($row[3]) : '';
            $phoneNumber = isset($row[4]) ? trim($row[4]) : '';

            $baseUsername = strtolower(str_replace(' ', '_', $name));
            $username = $baseUsername;
            $count = 1;

            while (in_array($username, $existingUsernames)) {
                $username = $baseUsername . '_' . $count;
                $count++;
            }

            $existingUsernames[] = $username;

            $data[] = [
                'name' => $name,
                'username' => $username,
                'date_of_birth' => $dateOfBirth,
                'gender' => $gender,
                'address' => $address,
                'phone_number' => $phoneNumber,
                'email' => $username . '@example.com',
                'password' => bcrypt('abc123'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        User::insert($data);
    }
}
