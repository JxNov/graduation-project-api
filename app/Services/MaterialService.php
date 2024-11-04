<?php
namespace App\Services;

use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialService
{
    public function createNewMaterial($data)
    {
        return DB::transaction(function () use ($data) {
            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $teacher = User::where('username', $data['username'])->first();

            if ($teacher === null) {
                throw new Exception('Giáo viên không tồn tại hoặc đã bị xóa');
            }

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;

            $data['slug'] = $subject->slug . '-' . Str::slug($data['title']);

            if (isset($data['file_path'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'materials/' . $data['file_path']->getClientOriginalName();

                $storage->upload(
                    file_get_contents($data['file_path']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
            }

            $data['file_path'] = $firebasePath;
            $material = Material::create($data);

            return $material;
        });
    }

    public function updateMaterial($data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $material = Material::where('slug', $slug)
                ->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $teacher = User::where('username', $data['username'])->first();

            if ($teacher === null) {
                throw new Exception('Giáo viên không tồn tại hoặc đã bị xóa');
            }

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;

            if (isset($data['file_path'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                $firebasePath = 'materials/' . $data['file_path']->getClientOriginalName();

                if ($material->file_path) {
                    $oldFirebasePath = $material->file_path;

                    $oldFile = $storage->object($oldFirebasePath);

                    if ($oldFile->exists()) {
                        $oldFile->delete();
                    }
                }

                $storage->upload(
                    file_get_contents($data['file_path']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );
                $data['file_path'] = $firebasePath;
            }

            $material->update($data);

            return $material;
        });
    }

    public function deleteMaterial($slug)
    {
        return DB::transaction(function () use ($slug) {
            $material = Material::where('slug', $slug)->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $material->delete();
        });
    }

    public function restoreMaterial($slug)
    {
        return DB::transaction(function () use ($slug) {
            $material = Material::where('slug', $slug)
                ->onlyTrashed()
                ->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại');
            }

            $material->restore();

            return $material;
        });
    }

    public function forceDeleteMaterial($slug)
    {
        return DB::transaction(function () use ($slug) {
            $material = Material::where('slug', $slug)->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            if ($material->file_path && Storage::exists($material->file_path)) {
                Storage::delete($material->file_path);
            }

            $material->forceDelete();
        });
    }
}