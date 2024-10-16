<?php
namespace App\Services;

use App\Models\Block;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlockService
{
    public function createNewBlock(array $data)
    {
        return DB::transaction(function () use ($data) {
            // lấy số cuối cùng từ chuỗi name gửi lên
            preg_match('/\d+(?!.*\d)/', $data['name'], $matches);
            $numberInName = isset($matches[0]) ? (int) $matches[0] : null;

            if ($numberInName !== null && $numberInName != (int) $data['level']) {
                throw new Exception('Khối: ' . $numberInName . ' không phù hợp với : ' . $data['level']);
            }

            $data['slug'] = Str::slug($data['name']);

            $block = Block::create($data);

            return $block;
        });
    }

    public function updateBlock(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $block = Block::where('slug', $slug)->first();

            if ($block === null) {
                throw new Exception('Không tìm thấy khối');
            }

            preg_match('/\d+(?!.*\d)/', $data['name'], $matches);
            $numberInName = isset($matches[0]) ? (int) $matches[0] : null;

            if ($numberInName !== null && $numberInName != (int) $data['level']) {
                throw new Exception('Khối: ' . $numberInName . ' không phù hợp với : ' . $data['level']);
            }

            $data['slug'] = Str::slug($data['name']);

            $block->update($data);

            return $block;
        });
    }

    public function deleteBlock($slug)
    {
        return DB::transaction(function () use ($slug) {
            $block = Block::where('slug', $slug)->first();
            if ($block === null) {
                throw new Exception('Không tìm thấy khối');
            }

            $block->delete();
            return $block;
        });
    }

    public function restoreBlock($slug)
    {
        return DB::transaction(function () use ($slug) {
            $block = Block::onlyTrashed()->where('slug', $slug)->first();
            if ($block === null) {
                throw new Exception('Không tìm thấy khối');
            }

            $block->restore();
            return $block;
        });
    }

    public function forceDeleteBlock($slug)
    {
        return DB::transaction(function () use ($slug) {
            $block = Block::withTrashed()->where('slug', $slug)->first();
            if ($block === null) {
                throw new Exception('Không tìm thấy khối');
            }

            $block->forceDelete();
            return $block;
        });
    }
}