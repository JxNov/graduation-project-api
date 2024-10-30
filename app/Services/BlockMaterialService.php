<?php
namespace App\Services;

use App\Models\Block;
use App\Models\BlockMaterial;
use App\Models\Material;
use Exception;
use Illuminate\Support\Facades\DB;

class BlockMaterialService
{
    public function createNewBlockMaterial($data)
    {
        return DB::transaction(function () use ($data) {
            $block = Block::where('slug', $data['block_slug'])->first();

            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $material = Material::where('slug', $data['material_slug'])->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $blockMaterialExists = BlockMaterial::where('block_id', $block->id)
                ->where('material_id', $material->id)
                ->first();

            if ($blockMaterialExists) {
                throw new Exception('Tài liệu đã có trong khối này');
            }

            $data['material_id'] = $material->id;
            $data['block_id'] = $block->id;

            $blockMaterial = BlockMaterial::create($data);
            return $blockMaterial;
        });
    }

    public function updateBlockMaterial($data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $blockMaterial = BlockMaterial::find($id);

            $block = Block::where('slug', $data['block_slug'])->first();

            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $material = Material::where('slug', $data['material_slug'])->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $blockMaterialExists = BlockMaterial::where('block_id', $block->id)
                ->where('material_id', $material->id)
                ->where('id', '!=', $id)
                ->first();

            if ($blockMaterialExists) {
                throw new Exception('Tài liệu đã có trong khối này');
            }

            $data['material_id'] = $material->id;
            $data['block_id'] = $block->id;

            $blockMaterial->update($data);
            return $blockMaterial;
        });
    }

    public function deleteBlockMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $blockMaterial = BlockMaterial::find($id);
            if ($blockMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của khối');
            }
            $blockMaterial->delete();
        });
    }

    public function restoreBlockMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $blockMaterial = BlockMaterial::onlyTrashed()
                ->where('id', $id)
                ->first();

            if ($blockMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của khối');
            }

            $blockMaterial->restore();

            return $blockMaterial;
        });
    }

    public function forceDeleteBlockMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $blockMaterial = BlockMaterial::find($id);
            if ($blockMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của khối');
            }
            $blockMaterial->forceDelete();
        });
    }
}