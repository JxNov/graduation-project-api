<?php
namespace App\Services;

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
                $class->students()->attach($user->id);
            }

            return $class;
        });
    }
}