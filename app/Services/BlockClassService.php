<?php
namespace App\Services;

use App\Models\BlockClass;
use Exception;
use Illuminate\Support\Facades\DB;

class BlockClassService
{
    public function createNewBlockClass($data)
    {
        return DB::transaction(function () use ($data) {
            $blockClassExists = BlockClass::where('block_id', $data['block_id'])
                ->where('class_id', $data['class_id'])
                ->first();

            if ($blockClassExists) {
                throw new Exception('Lớp học đã tồn tại trong khối học này rồi');
            }

            $blockClass = BlockClass::create($data);
            return $blockClass;
        });
    }

    public function updateBlockClass($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $blockClassExists = BlockClass::where('block_id', $data['block_id'])
            ->where('class_id', $data['class_id'])
            ->where('id', '!=', $id)
            ->first();

            if ($blockClassExists) {
                throw new Exception('Lớp học đã tồn tại trong khối học này rồi');
            }

            $blockClass = BlockClass::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $blockClass->update($data);
            return $blockClass;
        });
    }

    public function deleteBlockClass($id)
    {
        return DB::transaction(function () use ($id) {
            $blockClass = BlockClass::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $blockClass->delete();
            return $blockClass; 
        });
    }
}
