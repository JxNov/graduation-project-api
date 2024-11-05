<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Subject;
use App\Models\SubjectClasses;
use Exception;
use Illuminate\Support\Facades\DB;

class SubjectClassService
{
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Lấy thông tin của lớp bằng slug
            $class = Classes::where('slug', $data['class_slug'])->firstOrFail();
            if ($class === null) {
                throw new Exception('Class không tồn tại hoặc đã bị xóa');
            }
            $subject = Subject::where('slug', $data['subject_slugs'])->firstOrFail();
            if ($subject === null) {
                throw new Exception('Subject không tồn tại hoặc đã bị xóa');
            }
            // Duyệt qua từng `subject_slug` để kiểm tra và thêm môn học
            $existingSubjects = []; 
            foreach ($data['subject_slugs'] as $subject_slug) {
                $subject = Subject::where('slug', $subject_slug)->firstOrFail(); // Lấy thông tin môn học bằng slug

                // Kiểm tra nếu môn học đã tồn tại trong lớp
                if ($class->subjects()->where('subject_id', $subject->id)->exists()) {
                    $existingSubjects[] = $subject->name; // Thêm tên môn học vào danh sách nếu đã tồn tại
                }
            }

            // Nếu có môn học nào đã tồn tại, báo lỗi với danh sách tên môn học
            if (!empty($existingSubjects)) {
                throw new \Exception('Các môn học sau đã tồn tại trong lớp: ' . implode(', ', $existingSubjects));
            }

            // Thêm tất cả các môn học vào lớp bằng slug
            $subjectIds = Subject::whereIn('slug', $data['subject_slugs'])->pluck('id')->toArray();
            $class->subjects()->attach($subjectIds);

            return [
                'status' => true,
                'message' => 'Các môn học đã được thêm vào lớp thành công'
            ];
        });
    }

    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            // Tìm lớp bằng slug
            $class = Classes::where('slug', $data['class_slug'])->firstOrFail();

            // Tìm môn học mới bằng slug
            $newSubject = Subject::where('slug', $data['new_subject_slug'])->firstOrFail(); // Sửa từ subject_slugs thành new_subject_slug

            // Tìm thông tin từ bảng trung gian subject_class bằng ID
            $subjectClass = SubjectClasses::findOrFail($id);

            // Kiểm tra xem môn học có thuộc lớp không
            if ($subjectClass->class_id !== $class->id) {
                throw new \Exception('Môn học không thuộc lớp này.');
            }

            // Cập nhật môn học trong bảng trung gian
            $subjectClass->subject_id = $newSubject->id; // Cập nhật slug môn học mới
            $subjectClass->updated_at = now(); // Cập nhật thời gian
            $subjectClass->save();

            return [
                'status' => true,
                'message' => 'Môn học đã được cập nhật thành công trong lớp.'
            ];
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $subject = SubjectClasses::where('id', $id)->firstOrFail();
            if ($subject === null) {
                throw new Exception('Môn học trong lớp không tồn tại hoặc đã bị xóa');
            }
            $subject->delete();
            return $subject;
        });
    }
    public function backup($id)
    {
        return DB::transaction(function () use ($id) {
            $subject = SubjectClasses::where('id', $id)
                ->onlyTrashed()
                ->first();

            if ($subject === null) {
                throw new Exception('Không tìm thấy môn học trong lớp');
            }

            $subject->restore();
            return $subject;
        });
    }
    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $blockClass = SubjectClasses::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $blockClass->forceDelete();
            return $blockClass;
        });
    }
}
