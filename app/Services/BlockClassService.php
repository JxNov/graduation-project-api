<?php
namespace App\Services;

use App\Models\Block;
use App\Models\BlockClass;
use App\Models\Classes;
use Exception;
use Illuminate\Support\Facades\DB;

class BlockClassService
{
    public function createNewBlockClass($data)
    {
        return DB::transaction(function () use ($data) {
            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $blockClassExists = BlockClass::where('block_id', $block->id)
                ->where('class_id', $class->id)
                ->first();

            if ($blockClassExists) {
                throw new Exception('Lớp học đã tồn tại trong khối học này rồi');
            }

            $data['block_id'] = $block->id;
            $data['class_id'] = $class->id;

            $blockClass = BlockClass::create($data);
            return $blockClass;
        });
    }

    public function updateBlockClass($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $block = Block::where('slug', $data['block_slug'])->first();
            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $blockClassExists = BlockClass::where('block_id', $block->id)
                ->where('class_id', $class->id)
                ->where('id', '!=', $id)
                ->first();

            if ($blockClassExists) {
                throw new Exception('Lớp học đã tồn tại trong khối học này rồi');
            }

            $blockClass = BlockClass::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $data['block_id'] = $block->id;
            $data['class_id'] = $class->id;

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

    public function restoreBlockClass($id)
    {
        return DB::transaction(function () use ($id) {
            $blockClass = BlockClass::where('id', $id)
                ->onlyTrashed()
                ->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $blockClass->restore();
            return $blockClass;
        });
    }

    public function forceDeleteBlockClass($id)
    {
        return DB::transaction(function () use ($id) {
            $blockClass = BlockClass::where('id', $id)->first();

            if ($blockClass === null) {
                throw new Exception('Không tìm thấy lớp học của khối');
            }

            $blockClass->forceDelete();
            return $blockClass;
        });
    }
}
