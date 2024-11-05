<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Classes;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class SubjectService
{

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $class = Classes::where('slug', $data['class_slug'])->firstOrFail();
            if ($class === null) {
                throw new Exception('Class không tồn tại hoặc đã bị xóa');
            }
            $block = Block::where('slug', $data['block_slug'])->firstOrFail();;

            // Kiểm tra tên môn học có chứa số lớp tương ứng với block_level
            $gradeLevelFromName = $this->extractGradeLevel($data['name']);

            if ($gradeLevelFromName !== intval($data['block_level'])) {
                throw new Exception('Tên môn học và mã khối không khớp nhau.');
            }
            //tạo slug
            $data['slug'] = Str::slug($data['name'], '-');
            // Tạo môn học mới
            $subject = Subject::create($data);
            $subject->classes()->sync($class->id);
            $subject->blocks()->sync($block->id);
            return $subject;
        });
    }

    public function update(array $data,$slug)
    {
        return DB::transaction(function () use ($data,$slug) {
            $class = Classes::where('slug', $data['class_slug'])->firstOrFail();
            if ($class === null) {
                throw new Exception('Class không tồn tại hoặc đã bị xóa');
            }
            $block = Block::where('slug', $data['block_slug'])->firstOrFail();;
            $subject = Subject::where('slug',$slug)->first();

            // Kiểm tra tên môn học có chứa số lớp tương ứng với block_level
            $gradeLevelFromName = $this->extractGradeLevel($data['name']);

            if ($gradeLevelFromName !== intval($data['block_level'])) {
                throw new Exception('Tên môn học và mã khối không khớp nhau.');
            }

            $subject->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'block_level' => $data['block_level'],
            ]);
            $subject->classes()->sync($class->id);
            $subject->blocks()->sync($block->id);

            return $subject;
        });
    }


    public function destroy($slug)
{
    return DB::transaction(function () use ($slug) {

        $subject = Subject::where('slug', $slug)->firstOrFail();
        $subject->delete();

        return null; 
    });
}


    public function backup($slug)
    {
        return DB::transaction(function () use ($slug) {
            // Lấy môn học đã bị xóa
            $subject = Subject::withTrashed()->where('slug',$slug);
            // Khôi phục môn học
            $subject->restore();
            return $subject; // Trả về môn học đã được khôi phục

        });
    }
    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $blockClass = Subject::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy môn học');
            }

            $blockClass->forceDelete();
            return $blockClass;
        });
    }
    // Phương thức để trích xuất số lớp từ tên môn học
    private function extractGradeLevel($name)
    {
        // Sử dụng biểu thức chính quy để tìm số ở cuối tên môn học (vd: Toán 6)
        if (preg_match('/\d+$/', $name, $matches)) {
            return intval($matches[0]);
        }

        // Nếu không tìm thấy số lớp, ném ra lỗi
        throw new Exception('Tên môn học không chứa số lớp.');
    }
}
