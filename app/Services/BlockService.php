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