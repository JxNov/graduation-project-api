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
        // Tìm khối theo slug
        $block = Block::where('slug', $data['block_slug'])->firstOrFail();
        
        // Duyệt qua từng môn học và thêm vào cơ sở dữ liệu
        $subjects = [];
        foreach ($data['subjects'] as $subjectData) {
            $subjectData['slug'] = Str::slug($subjectData['name'], '-');

            // Tạo môn học
            $subject = Subject::create($subjectData);

            // Gán môn học cho các lớp thuộc khối này
            $classes = $block->classes()->get(); // Thêm get() để lấy kết quả

            foreach ($classes as $class) {
                // Gắn môn học vào lớp
                $subject->classes()->attach($class->id);
            }

            // Gắn môn học vào khối
            $subject->blocks()->attach($block->id);

            $subjects[] = $subject;
        }

        return $subjects;
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

            $subject->update([
                'name' => $data['name'],
                'description' => $data['description'],
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
            $subject = Subject::withTrashed()->where('slug',$slug);
            $subject->restore();
            return $subject; 

        });
    }
    public function forceDelete($slug)
    {
        return DB::transaction(function () use ($slug) {
            $blockClass = Subject::where('slug', $slug)->first();
            if ($blockClass === null) {
                throw new Exception('Không tìm thấy môn học');
            }
            $blockClass->forceDelete();
            return $blockClass;
        });
    }
}
