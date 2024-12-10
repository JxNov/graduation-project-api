<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Generation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentService
{
    public function createStudent(array $data)
    {
        return DB::transaction(function () use ($data) {
            $academicYear = AcademicYear::where('slug', $data['academicYearSlug'])->firstOrFail();
            $generation = Generation::where('slug', $data['generationSlug'])->firstOrFail();
            // Tạo username duy nhất
            $username = $this->generateUsername($data['name']);

            // Tạo email dựa trên tên và username
            $data['email'] = $this->generateEmail($username);
            if (isset($data['image'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'image-user/' . Str::random(9) . $data['image']->getClientOriginalName();

                $storage->upload(
                    file_get_contents($data['image']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
            }

            $data['image'] = $firebasePath;
            // Lưu thông tin học sinh vào cơ sở dữ liệu
            $student = User::create([
                'name' => $data['name'],
                'username' => $username,
                'image' => $data['image'],
                'email' => $data['email'],
                'password' => Hash::make('abc123456'), // Mật khẩu mặc định
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
            ]);

            // Lấy ID role cho học sinh
            $roleStudent = Role::where('slug', 'student')->first();

            if (!$roleStudent) {
                throw new \Exception("Quyền 'student' không tồn tại trong hệ thống.");
            }
            // Gắn role 'student' cho học sinh
            if ($roleStudent) {
                DB::table('user_roles')->insert([
                    'user_id' => $student->id,
                    'role_id' => $roleStudent->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('user_generations')->insert([
                    'user_id' => $student->id,
                    'generation_id' => $generation->id,
                    'academic_year_id' => $academicYear->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $student;
        });
    }

    public function updateStudent($data, $username)
    {
        return DB::transaction(function () use ($data, $username) {
            $user = User::where('username', $username)->firstOrFail();

            // Tách phần đuôi `ps` + 5 số ngẫu nhiên từ `username` hiện tại
            if (str_starts_with($user->username, strtolower($this->removeAccents($data['name'])))) {
                $username = $user->username;
            } else {
                // Nếu thay đổi tên, tạo username mới nhưng giữ lại phần đuôi `ps` + 5 số ngẫu nhiên
                $usernameBase = $this->generateUsernameBase($data['name']);

                // Tách phần đuôi `ps` + 5 số từ username hiện tại
                $suffix = substr($user->username, strpos($user->username, 'ps'));

                // Ghép phần mới với đuôi cũ
                $username = $usernameBase . $suffix;
            }


            // Cập nhật thông tin người dùng
            $data['email'] = $this->generateEmail($username);
            if (isset($data['image'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'image-user/' . $data['image']->getClientOriginalName();

                if ($user->image) {
                    $oldFirebasePath = $user->image;

                    $oldFile = $storage->object($oldFirebasePath);

                    if ($oldFile->exists()) {
                        $oldFile->delete();
                    }
                }

                $storage->upload(
                    file_get_contents($data['image']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
                $data['image'] = $firebasePath;
            }
            $user->update([
                'name' => $data['name'],
                'username' => $username,
                'image' => $data['image'],
                'email' => $data['email'],
                'password' => Hash::make('abc123456'), // Mật khẩu mặc định
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
            ]);

            return $user;
        });
    }

    public function destroy($username)
    {
        return DB::transaction(function () use ($username) {
            $user = User::where('username', $username)->firstOrFail();

            // Xóa mềm người dùng
            $user->delete();

            // Xóa mềm các vai trò trong bảng `user_role`
            $user->roles()->each(function ($role) use ($user) {
                $user->roles()->updateExistingPivot($role->id, ['deleted_at' => now()]);
            });

            return $user;
        });
    }

    public function backup($username)
    {
        return DB::transaction(function () use ($username) {
            $user = User::where('username', $username)->withTrashed()->firstOrFail();

            // Khôi phục người dùng
            $user->restore();

            // Khôi phục các vai trò trong bảng `user_role`
            $user->roles()->withTrashed()->each(function ($role) use ($user) {
                $user->roles()->updateExistingPivot($role->id, ['deleted_at' => null]);
            });

            return $user;
        });
    }

    public function generateUsername($fullName)
    {
        // Làm sạch chuỗi tên
        $cleanedName = $this->removeAccents($this->cleanString($fullName));

        // Tách các phần của tên
        $nameParts = explode(" ", $cleanedName);
        $firstName = strtolower(array_pop($nameParts)); // Chuyển thành chữ thường
        $lastNameInitial = strtolower(substr(array_shift($nameParts), 0, 1));

        // Lấy ký tự đầu tiên của từng từ trong tên đệm
        $middleNameInitials = '';
        foreach ($nameParts as $middleName) {
            $middleNameInitials .= strtolower(substr($middleName, 0, 1));
        }

        // Tạo phần gốc của username
        $usernameBase = strtolower($firstName . $lastNameInitial . $middleNameInitials);

        // Thêm "ps" và 5 chữ số ngẫu nhiên
        return $usernameBase . 'ps' . rand(10000, 99999);
    }

    public function generateEmail($username)
    {
        return $username . '@tech4school.edu.vn';
    }
    private function removeAccents($string)
    {
        $accents = [
            'á',
            'à',
            'ả',
            'ã',
            'ạ',
            'ă',
            'ắ',
            'ằ',
            'ẳ',
            'ẵ',
            'ặ',
            'â',
            'ấ',
            'ầ',
            'ẩ',
            'ẫ',
            'ậ',
            'đ',
            'é',
            'è',
            'ẻ',
            'ẽ',
            'ẹ',
            'ê',
            'ế',
            'ề',
            'ể',
            'ễ',
            'ệ',
            'í',
            'ì',
            'ỉ',
            'ĩ',
            'ị',
            'ó',
            'ò',
            'ỏ',
            'õ',
            'ọ',
            'ô',
            'ố',
            'ồ',
            'ổ',
            'ỗ',
            'ộ',
            'ơ',
            'ớ',
            'ờ',
            'ở',
            'ỡ',
            'ợ',
            'ú',
            'ù',
            'ủ',
            'ũ',
            'ụ',
            'ư',
            'ứ',
            'ừ',
            'ử',
            'ữ',
            'ự',
            'ý',
            'ỳ',
            'ỷ',
            'ỹ',
            'ỵ',
            'Á',
            'À',
            'Ả',
            'Ã',
            'Ạ',
            'Ă',
            'Ắ',
            'Ằ',
            'Ẳ',
            'Ẵ',
            'Ặ',
            'Â',
            'Ấ',
            'Ầ',
            'Ẩ',
            'Ẫ',
            'Ậ',
            'Đ',
            'É',
            'È',
            'Ẻ',
            'Ẽ',
            'Ẹ',
            'Ê',
            'Ế',
            'Ề',
            'Ể',
            'Ễ',
            'Ệ',
            'Í',
            'Ì',
            'Ỉ',
            'Ĩ',
            'Ị',
            'Ó',
            'Ò',
            'Ỏ',
            'Õ',
            'Ọ',
            'Ô',
            'Ố',
            'Ồ',
            'Ổ',
            'Ỗ',
            'Ộ',
            'Ơ',
            'Ớ',
            'Ờ',
            'Ở',
            'Ỡ',
            'Ợ',
            'Ú',
            'Ù',
            'Ủ',
            'Ũ',
            'Ụ',
            'Ư',
            'Ứ',
            'Ừ',
            'Ử',
            'Ữ',
            'Ự',
            'Ý',
            'Ỳ',
            'Ỷ',
            'Ỹ',
            'Ỵ'
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
            'd',
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
            'D',
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
            'Y'
        ];

        return str_replace($accents, $noAccents, $string);
    }

    private function cleanString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
    private function generateUsernameBase($fullName)
    {
        // Xử lý logic tạo phần gốc của `username` như trong `generateUsername` nhưng không thêm đuôi `ps` và số ngẫu nhiên
        $cleanedName = $this->removeAccents($this->cleanString($fullName));

        // Tách các phần của tên
        $nameParts = explode(" ", $cleanedName);
        $firstName = array_pop($nameParts);
        $lastNameInitial = strtolower(substr(array_shift($nameParts), 0, 1));

        // Lấy ký tự đầu tiên của từng tên đệm
        $middleNameInitials = '';
        foreach ($nameParts as $middleName) {
            $middleNameInitials .= strtolower(substr($middleName, 0, 1));
        }

        return strtolower($firstName . $lastNameInitial . $middleNameInitials);
    }
}
