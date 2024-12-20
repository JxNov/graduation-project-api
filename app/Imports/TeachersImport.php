<?php

namespace App\Imports;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class TeachersImport implements ToCollection, WithChunkReading, ShouldQueue
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows)
    {
        $data = [];
        $existingUsernames = User::pluck('username')->toArray();
        $roleTeacher = Role::select('id', 'slug')->where('slug', 'teacher')->first();

        if ($roleTeacher === null) {
            throw new \Exception('Không tìm thấy vai trò là giáo viên');
        }

        foreach ($rows as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $name = trim($row[0] ?? '');
            $name = $this->cleanString($name);

            $dateOfBirth = isset($row[1]) ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1]))->format('Y-m-d') : null;
            $gender = trim($row[2] ?? '');
            $address = trim($row[3] ?? '');
            $phoneNumber = trim($row[4] ?? '');

            if (empty($name)) {
                continue; // Bỏ qua nếu tên trống
            }

            $gender = $this->cleanString($gender);
            $address = $this->cleanString($address);
            $phoneNumber = $this->cleanString($phoneNumber);

            // Tạo username mới
            $username = $this->generateUsername($name, $existingUsernames);

            $data[] = [
                'name' => $name,
                'username' => $username,
                'date_of_birth' => $dateOfBirth,
                'gender' => $gender,
                'address' => $address,
                'phone_number' => $phoneNumber,
                'email' => $this->generateEmail($username),
                'password' => Hash::make(env('PASSWORD_DEFAULT')),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $inserted = User::insert($data);

        if ($inserted) {
            $emails = [];

            foreach ($data as $email) {
                $emails[] = $email['email'];
            }

            $users = User::whereIn('email', $emails)->get();

            foreach ($users as $user) {
                $userHasRole = DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->where('role_id', $roleTeacher->id)
                    ->exists();

                if (!$userHasRole) {
                    DB::table('user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $roleTeacher->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
    public function generateUsername($fullName, $existingUsernames)
    {
        // Làm sạch chuỗi tên và loại bỏ dấu
        $cleanedName = $this->removeAccents($fullName);

        // Tách các phần của tên
        $nameParts = explode(" ", $cleanedName);

        // Lấy tên đầu tiên
        $firstName = strtolower(array_pop($nameParts)); // Tên đầu tiên
        $lastName = strtolower(array_shift($nameParts)); // Họ
        $lastNameInitial = substr($lastName, 0, 1); // Chữ cái đầu của họ

        // Lấy chữ cái đầu của từng tên đệm
        $middleNameInitials = '';
        foreach ($nameParts as $middleName) {
            if (!empty($middleName)) { // Kiểm tra nếu tên đệm không trống
                $middleNameInitials .= strtolower(substr($middleName, 0, 1)); // Chữ cái đầu của tên đệm
            }
        }

        // Tạo phần gốc của username
        $usernameBase = $firstName . $lastNameInitial . $middleNameInitials; // Tên + chữ cái đầu của họ + chữ cái đầu tên đệm

        // Đảm bảo username là duy nhất
        $username = $usernameBase . rand(10, 999);
        while (in_array($username, $existingUsernames)) {
            $username = $usernameBase . rand(10, 999);
        }

        return $username;
    }


    // Hàm tạo email
    public function generateEmail($username)
    {
        return $username . '@tech4school.edu.vn';
    }

    // Hàm loại bỏ dấu
    private function removeAccents($string)
    {
        $accents = [
            'à',
            'á',
            'ạ',
            'ả',
            'ã',
            'â',
            'ầ',
            'ấ',
            'ậ',
            'ẩ',
            'ẫ',
            'ă',
            'ằ',
            'ắ',
            'ặ',
            'ẳ',
            'ẵ',
            'è',
            'é',
            'ẹ',
            'ẻ',
            'ẽ',
            'ê',
            'ề',
            'ế',
            'ệ',
            'ể',
            'ễ',
            'ì',
            'í',
            'ị',
            'ỉ',
            'ĩ',
            'ò',
            'ó',
            'ọ',
            'ỏ',
            'õ',
            'ô',
            'ồ',
            'ố',
            'ộ',
            'ổ',
            'ỗ',
            'ơ',
            'ờ',
            'ớ',
            'ợ',
            'ở',
            'ỡ',
            'ù',
            'ú',
            'ụ',
            'ủ',
            'ũ',
            'ư',
            'ừ',
            'ứ',
            'ự',
            'ử',
            'ữ',
            'ỳ',
            'ý',
            'ỵ',
            'ỷ',
            'ỹ',
            'À',
            'Á',
            'Ạ',
            'Ả',
            'Ã',
            'Â',
            'Ầ',
            'Ấ',
            'Ậ',
            'Ẩ',
            'Ẫ',
            'Ă',
            'Ằ',
            'Ắ',
            'Ặ',
            'Ẳ',
            'Ẵ',
            'È',
            'É',
            'Ẹ',
            'Ẻ',
            'Ẽ',
            'Ê',
            'Ề',
            'Ế',
            'Ệ',
            'Ể',
            'Ễ',
            'Ì',
            'Í',
            'Ị',
            'Ỉ',
            'Ĩ',
            'Ò',
            'Ó',
            'Ọ',
            'Ỏ',
            'Õ',
            'Ô',
            'Ồ',
            'Ố',
            'Ộ',
            'Ổ',
            'Ỗ',
            'Ơ',
            'Ờ',
            'Ớ',
            'Ợ',
            'Ở',
            'Ỡ',
            'Ù',
            'Ú',
            'Ụ',
            'Ủ',
            'Ũ',
            'Ư',
            'Ừ',
            'Ứ',
            'Ự',
            'Ử',
            'Ữ',
            'Ỳ',
            'Ý',
            'Ỵ',
            'Ỷ',
            'Ỹ',
            'Đ',
            'đ'
        ];

        $noAccents = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'y',
            'y',
            'y',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'Y',
            'Y',
            'Y',
            'Y',
            'D',
            'd'
        ];

        return str_replace($accents, $noAccents, $string);
    }

    private function cleanString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
