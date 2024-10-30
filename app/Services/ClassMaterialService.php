<?php
namespace App\Services;

use App\Models\Classes;
use App\Models\ClassMaterial;
use App\Models\Material;
use Exception;
use Illuminate\Support\Facades\DB;

class ClassMaterialService
{
    public function createNewClassMaterial($data)
    {
        return DB::transaction(function () use ($data) {
            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $material = Material::where('slug', $data['material_slug'])->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $classMaterialExists = ClassMaterial::where('class_id', $class->id)
                ->where('material_id', $material->id)
                ->first();

            if ($classMaterialExists) {
                throw new Exception('Tài liệu này đã có trong lớp này');
            }

            $data['material_id'] = $material->id;
            $data['class_id'] = $class->id;

            $classMaterial = ClassMaterial::create($data);
            return $classMaterial;
        });
    }

    public function updateClassMaterial($data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $classMaterial = ClassMaterial::find($id);
            if ($classMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của lớp');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $material = Material::where('slug', $data['material_slug'])->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $classMaterialExists = ClassMaterial::where('class_id', $class->id)
                ->where('material_id', $material->id)
                ->where('id', '!=', $id)
                ->first();

            if ($classMaterialExists) {
                throw new Exception('Tài liệu này đã có trong lớp này');
            }

            $data['material_id'] = $material->id;
            $data['class_id'] = $class->id;

            $classMaterial->update($data);
            return $classMaterial;
        });
    }

    public function deleteClassMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $classMaterial = ClassMaterial::find($id);
            if ($classMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của lớp');
            }
            $classMaterial->delete();
        });
    }

    public function restoreClassMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $classMaterial = ClassMaterial::onlyTrashed()
                ->where('id', $id)
                ->first();

            if ($classMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của lớp');
            }

            $classMaterial->restore();

            return $classMaterial;
        });
    }

    public function forceDeleteClassMaterial($id)
    {
        return DB::transaction(function () use ($id) {
            $classMaterial = ClassMaterial::find($id);
            if ($classMaterial === null) {
                throw new Exception('Không tìm thấy tài liệu của lớp');
            }
            $classMaterial->forceDelete();
        });
    }
}