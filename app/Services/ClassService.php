<?php
namespace App\Services;

use App\Models\Classes;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassService
{
    public function createNewClass(array $data)
    {
        return DB::transaction(function () use ($data) {
            // lấy tất cả giáo viên
            $teacher = User::whereHas('roles', function ($role) {
                $role->where('slug', 'like', 'teacher');
            })
                ->where('username', $data['username'])
                ->first();

            if ($teacher === null) {
                throw new Exception('Người này không phải giáo viên');
            }

            // kiểm tra nếu giáo viên đã có trong 1 lớp cùng năm học
            $homeRoomTeacher = Classes::where('teacher_id', $teacher->id)
                ->first();

            if ($homeRoomTeacher) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $teacherName = $teacher->name;
            $teacherSlug = Str::slug($teacherName);
            $classSlug = Str::slug($data['name']);
            $data['slug'] = $teacherSlug . '-' . $classSlug;

            return Classes::create($data);
        });
    }

    public function updateClass(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $teacher = User::whereHas('roles', function ($role) {
                $role->where('slug', 'like', 'teacher');
            })
                ->where('username', $data['username'])
                ->first();

            if ($teacher === null) {
                throw new Exception('Người này không phải giáo viên');
            }

            $homeRoomTeacher = Classes::where('teacher_id', $teacher->id)
                ->first();

            if ($homeRoomTeacher && $homeRoomTeacher->id !== $class->id) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $class->update($data);

            return $class;
        });
    }

    public function deleteClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $class->delete();
        });
    }

    public function restoreClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::onlyTrashed()->where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $class->restore();

            return $class;
        });
    }

    public function forceDeleteClass($slug)
    {
        return DB::transaction(function () use ($slug) {
            $class = Classes::withTrashed()->where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $class->forceDelete();
        });
    }
}
