<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\User;
use App\Models\Semester;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Notifications\NewAssignmentNotification;

class AssignmentService
{
    public function createNewAssignment($data)
    {
        return DB::transaction(function () use ($data) {
            $subject = Subject::where('slug', $data['subject_slug'])->first();
            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();
            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $teacher = User::where('username', $data['username'])->first();
            if ($teacher === null) {
                throw new Exception('Giáo viên không tồn tại hoặc đã bị xóa');
            }

            $semester = Semester::where('slug', $data['semester_slug'])->first();
            if ($semester === null) {
                throw new Exception('Học kỳ không tồn tại hoặc đã bị xóa');
            }

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;
            $data['class_id'] = $class->id;
            $data['semester_id'] = $semester->id;

            // Tạo slug
            $classSlug = Str::slug($class->slug);
            $assignmentNameSlug = Str::slug($data['title']);
            $data['slug'] = $classSlug . '-' . $assignmentNameSlug;

            $assignment = Assignment::create($data);

            foreach ($class->students as $student) {
                $student->notify(new NewAssignmentNotification($assignment));
            }

            return $assignment;
        });
    }

    public function updateAssignment($slug, $data)
    {
        return DB::transaction(function () use ($data, $slug) {
            $assignment = Assignment::where('slug', $slug)->first();
            if ($assignment === null) {
                throw new Exception('Bài tập không tồn tại hoặc đã bị xóa');
            }

            $subject = Subject::where('slug', $data['subject_slug'])->first();
            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();
            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $teacher = User::where('username', $data['username'])->first();
            if ($teacher === null) {
                throw new Exception('Giáo viên không tồn tại hoặc đã bị xóa');
            }

            $semester = Semester::where('slug', $data['semester_slug'])->first();
            if ($semester === null) {
                throw new Exception('Học kỳ không tồn tại hoặc đã bị xóa');
            }

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;
            $data['class_id'] = $class->id;
            $data['semester_id'] = $semester->id;

            // Giữ nguyên slug
            unset($data['slug']);

            $assignment->update($data);

            return $assignment;
        });
    }

    public function deleteAssignment($slug)
    {
        return DB::transaction(function () use ($slug) {
            $assignment = Assignment::where('slug', $slug)->first();
            if ($assignment === null) {
                throw new Exception('Bài tập không tồn tại hoặc đã bị xóa');
            }

            $assignment->delete();
        });
    }

    public function restoreAssignment($slug)
    {
        return DB::transaction(function () use ($slug) {
            $assignment = Assignment::where('slug', $slug)->onlyTrashed()->first();
            if ($assignment === null) {
                throw new Exception('Bài tập không tồn tại');
            }

            $assignment->restore();

            return $assignment;
        });
    }

    public function forceDeleteAssignment($slug)
    {
        return DB::transaction(function () use ($slug) {
            $assignment = Assignment::where('slug', $slug)->first();
            if ($assignment === null) {
                throw new Exception('Bài tập không tồn tại hoặc đã bị xóa');
            }

            $assignment->forceDelete();
        });
    }
}
