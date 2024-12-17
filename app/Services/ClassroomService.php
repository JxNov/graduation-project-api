<?php
namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classes;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassroomService
{
    public function joinClassroomByCode($code)
    {
        return DB::transaction(function () use ($code) {
            $user = Auth::user();
            $class = Classes::where('code', $code)->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            if ($user->roles()->where('name', 'teacher')->exists()) {
                $class->classTeachers()->attach($user->id);
            } elseif ($user->roles()->where('name', 'student')->exists()) {
                $academicYearClass = DB::table('academic_year_classes')->where('class_id', $class->id)->first();
                // dd($academicYearClass);
                $class->students()->attach($user->id);
                $academicYear = AcademicYear::find($academicYearClass->academic_year_id);
                $existsGenerationUser = DB::table('user_generations')->where('user_id', $user->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('generation_id', $academicYear->generation_id)->first();

                if (!$existsGenerationUser) {
                    DB::table('user_generations')->insert([
                        'user_id' => $user->id,
                        'generation_id' => $academicYear->generation_id,
                        'academic_year_id' => $academicYear->id,
                    ]);
                }
            }

            return $class;
        });
    }
}