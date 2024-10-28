<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Generation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentsImport implements ToCollection
{
    private $generationSlug;
    private $academicYearSlug;

    public function __construct(string $generationSlug, string $academicYearSlug)
    {
        $this->generationSlug = $generationSlug;
        $this->academicYearSlug = $academicYearSlug;
    }

    public function collection(Collection $rows)
    {
        $data = [];
        $existingUsernames = User::pluck('username')->toArray();
        $roleStudent = DB::table('roles')->select('id', 'slug')->where('slug', 'student')->first();

        if ($roleStudent === null) {
            throw new \Exception('Không tìm thấy vai trò là học sinh');
        }

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
            ];
        }

        $inserted = User::insert($data);

        if ($inserted) {
            $emails = [];

            foreach ($data as $email) {
                $emails[] = $email['email'];
            }

            $users = User::whereIn('email', $emails)->get();
            $generation = Generation::where('slug', $this->generationSlug)->first();
            $academic_year = AcademicYear::where('slug', $this->academicYearSlug)->first();

            foreach ($users as $user) {
                $userHasRole = DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->where('role_id', $roleStudent->id)
                    ->exists();

                $userHasGeneration = DB::table('user_generations')
                    ->where('user_id', $user->id)
                    ->where('generation_id', $generation->id)
                    ->where('academic_year_id', $academic_year->id)
                    ->exists();

                if (!$userHasRole) {
                    DB::table('user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $roleStudent->id,
                    ]);
                }

                if (!$userHasGeneration) {
                    DB::table('user_generations')->insert([
                        'user_id' => $user->id,
                        'generation_id' => $generation->id,
                        'academic_year_id' => $academic_year->id,
                    ]);
                }
            }
        }
    }
}
