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
            $teachers = User::with('roles')
                ->whereHas('roles', function ($role) {
                    $role->where('slug', 'like', 'teacher');
                })
                ->pluck('name', 'id');

            if (!$teachers->has($data['teacher_id'])) {
                throw new Exception('Người này không phải giáo viên');
            }

            $homeRoomTeacher = Classes::where('teacher_id', $data['teacher_id'])->first();

            if ($homeRoomTeacher) {
                throw new Exception('Giáo viên đã có trong một lớp');
            }

            $teacherName = $teachers->get($data['teacher_id']);
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

            $teachers = User::with('roles')
                ->whereHas('roles', function ($role) {
                    $role->where('slug', 'like', 'teacher');
                })
                ->pluck('name', 'id');

            if (!$teachers->has($data['teacher_id'])) {
                throw new Exception('Người này không phải giáo viên');
            }

            $homeRoomTeacher = Classes::where('teacher_id', $data['teacher_id'])->first();

            if ($homeRoomTeacher && $homeRoomTeacher->id !== $class->id) {
                throw new Exception('Giáo viên đã có trong 1 lớp');
            }

            $teacherName = $teachers->get($data['teacher_id']);
            $teacherSlug = Str::slug($teacherName);
            $classSlug = Str::slug($data['name'] ?? $class->name);
            $data['slug'] = $teacherSlug . '-' . $classSlug;

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
