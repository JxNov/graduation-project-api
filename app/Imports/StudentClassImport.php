<?php

namespace App\Imports;

use App\Models\Generation;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentClassImport implements ToCollection
{
    private $generationSlug;
    private $academicYearSlug;

    private $classSlug;
    public function __construct(string $generationSlug, string $academicYearSlug, string $classSlug)
    {
        $this->generationSlug = $generationSlug;
        $this->academicYearSlug = $academicYearSlug;
        $this->classSlug = $classSlug;
    }

    public function collection(Collection $rows)
{
    $data = [];
    $existingUsernames = User::pluck('username')->toArray();
    $roleStudent = Role::select('id', 'slug')->where('slug', 'student')->first();

    if ($roleStudent === null) {
        throw new \Exception('Không tìm thấy vai trò là học sinh');
    }

    foreach ($rows as $key => $row) {
        if ($key == 0) {
            continue;
        }

        // Lấy và làm sạch các trường dữ liệu
        $name = trim($row[0] ?? '');
        $name = $this->cleanString($name);
        $dateOfBirth = isset($row[1]) ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1]))->format('Y-m-d') : null;
        $gender = trim($row[2] ?? '');
        $address = trim($row[3] ?? '');
        $phoneNumber = trim($row[4] ?? '');

        // Bỏ qua nếu tên trống
        if (empty($name)) {
            continue;
        }

        $gender = $this->cleanString($gender);
        $address = $this->cleanString($address);
        $phoneNumber = $this->cleanString($phoneNumber);

        // Tạo username mới và email
        $username = $this->generateUsername($name, $existingUsernames);
        $email = $this->generateEmail($username);

        // Chuẩn bị dữ liệu để insert vào bảng users
        $data[] = [
            'name' => $name,
            'username' => $username,
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'address' => $address,
            'phone_number' => $phoneNumber,
            'email' => $email,
            'password' => Hash::make(env('PASSWORD_DEFAULT')),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Bước 1: Insert tất cả dữ liệu vào bảng users
    $inserted = User::insert($data);
    
    if ($inserted) {
        // Lấy tất cả học sinh đã insert
        $users = User::whereIn('email', array_column($data, 'email'))->get();
        // Lấy thông tin của khóa học, năm học và lớp
        $generation = Generation::where('slug', $this->generationSlug)->first();
        $academicYear = $generation->academicYears()->where('slug', $this->academicYearSlug)->first();
        $class = $academicYear->classes()->where('slug', $this->classSlug)->first();

        if (!$generation || !$academicYear || !$class) {
            return response()->json(['error' => 'Không tìm thấy thông tin cần thiết (Khóa học, Năm học, Lớp)'], 404);
        }

        // Bước 2: Gán vai trò cho học sinh
        foreach ($users as $user) {
            // Gán vai trò cho học sinh nếu chưa có
            $userHasRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $roleStudent->id)
                ->exists();

            if (!$userHasRole) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleStudent->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Gán học sinh vào khóa học nếu chưa có
            $userHasGeneration = DB::table('user_generations')
                ->where('user_id', $user->id)
                ->where('generation_id', $generation->id)
                ->where('academic_year_id', $academicYear->id)
                ->exists();

            if (!$userHasGeneration) {
                DB::table('user_generations')->insert([
                    'user_id' => $user->id,
                    'generation_id' => $generation->id,
                    'academic_year_id' => $academicYear->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Gán học sinh vào lớp trong bảng trung gian `class_student`
            $userHasClass = DB::table('class_students')
                ->where('student_id', $user->id)
                ->where('class_id', $class->id)
                ->exists();

            if (!$userHasClass) {
                DB::table('class_students')->insert([
                    'student_id' => $user->id,
                    'class_id' => $class->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    return response()->json(['success' => 'Dữ liệu đã được nhập thành công']);
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
        $username = $usernameBase . 'ps' . rand(10000, 99999);
        while (in_array($username, $existingUsernames)) {
            $username = $usernameBase . 'ps' . rand(10000, 99999);
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
