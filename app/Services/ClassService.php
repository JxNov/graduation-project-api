<?php
namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Block;
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
            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

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
                ->whereHas('academicYears', function ($query) use ($academicYear) {
                    $query->where('academic_years.id', $academicYear->id);
                })
                ->first();

            if ($homeRoomTeacher) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $teacherSlug = Str::slug($teacher->username);
            $classSlug = Str::slug($data['name']);
            $data['slug'] = $teacherSlug . '-' . $classSlug;

            $class = Classes::create($data);

            $class->academicYears()->sync($academicYear->id);
            $class->blocks()->sync($block->id);

            return $class;
        });
    }

    public function updateClass(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

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
                ->whereHas('academicYears', function ($query) use ($academicYear) {
                    $query->where('academic_years.id', $academicYear->id);
                })
                ->first();

            if ($homeRoomTeacher && $homeRoomTeacher->id !== $class->id) {
                throw new Exception('Giáo viên đã chủ nhiệm một lớp của năm học này');
            }

            $data['teacher_id'] = $teacher->id;

            $class->update($data);

            $class->academicYears()->sync($academicYear->id);
            $class->blocks()->sync($block->id);

            return $class;
        });
    }

    public function assignClassToTeacher(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $class = Classes::where('slug', $slug)->first();

            if ($class === null) {
                throw new Exception('Không tìm thấy lớp');
            }

            $userIds = [];
            $users = User::whereIn('username', $data['username'])->get();
            $userIds = $users->pluck('id')->toArray();

            $class->classTeachers()->sync($userIds);

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