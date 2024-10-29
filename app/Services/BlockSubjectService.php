<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\DB;

class BlockSubjectService
{
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Lấy thông tin của block và subject
            $block = Block::findOrFail($data['block_id']);
            $subject = Subject::findOrFail($data['subject_id']);

            // Bạn kiểm tra xem môn học có thuộc lớp mà block đại diện không
            if ($subject->block_level !== $block->level) {
                throw new \Exception('Môn học ' . $subject->name . ' không thể thêm vào khối lớp ' . $block->level . ' chỉ được thêm vào khối ' . $subject->block_level);
            }

            // Kiểm tra xem subject đã tồn tại trong block chưa
            if ($block->subjects()->where('subject_id', $subject->id)->exists()) {
                throw new \Exception('Môn học đã tồn tại trong khối này');
            }

            // Thêm môn học vào khối
            $block->subjects()->attach($subject->id);

            return [
                'status' => true,
                'message' => 'Thêm môn học vào khối thành công'
            ];
        });
    }



    public function update(array $data, $id) {}

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {

            // Tìm đối tượng block_subject cần xóa 
            $blockSubject = Block::findOrFail($id);
            // Xóa đối tượng
            $blockSubject->subjects()->detach();
            return $blockSubject;
        });
    }
}
