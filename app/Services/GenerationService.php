<?php

namespace App\Services;
use App\Models\Generation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerationService
{
    public function createNewGeneration(array $data)
    {
        return DB::transaction(function () use ($data) {
            $generations = Generation::select('start_date')->get();

            $startDate = Carbon::createFromFormat('d/m/Y', $data['start_date']);
            $endDate = Carbon::createFromFormat('d/m/Y', $data['end_date']);

            $generationTotalYear = $endDate->year - $startDate->year;
            if ($generationTotalYear !== (int) $data['year']) {
                throw new Exception('Khóa học cần có độ dài là ' . $data['year'] . ' năm.');
            }

            if ($generations->isNotEmpty()) {
                foreach ($generations as $generation) {
                    $existingStart = Carbon::parse($generation->start_date);

                    if ($startDate->year <= $existingStart->year) {
                        throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn năm bắt đầu của khóa học trước.');
                    }
                }
            }

            $data['slug'] = Str::slug($data['name']);

            $newGeneration = Generation::create($data);

            return $newGeneration;
        });
    }

    public function updateGeneration(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $currentGeneration = Generation::where('slug', $slug)->first();

            if ($currentGeneration === null) {
                throw new Exception('Không tìm thấy khóa học này');
            }

            $startDate = Carbon::createFromFormat('d/m/Y', $data['start_date']);
            $endDate = Carbon::createFromFormat('d/m/Y', $data['end_date']);

            $generationTotalYear = $endDate->year - $startDate->year;
            if ($generationTotalYear !== (int) $data['year']) {
                throw new Exception('Khóa học cần có độ dài là ' . $data['year'] . ' năm.');
            }

            $generations = Generation::where('id', '<', $currentGeneration->id)
                ->select('start_date')
                ->get();

            if ($generations->isNotEmpty()) {
                foreach ($generations as $generation) {
                    $existingStart = Carbon::parse($generation->start_date);

                    if ($startDate->year <= $existingStart->year) {
                        throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn năm bắt đầu của khóa học trước.');
                    }
                }
            }

            $data['slug'] = Str::slug($data['name']);

            $currentGeneration->update($data);

            return $currentGeneration;
        });
    }

    public function deleteGeneration($slug)
    {
        return DB::transaction(function () use ($slug) {
            $generation = Generation::where('slug', $slug)->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->delete();

            return $generation;
        });
    }

    public function restoreGeneration($slug)
    {
        return DB::transaction(function () use ($slug) {
            $generation = Generation::onlyTrashed()->where('slug', $slug)->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->restore();

            return $generation;
        });
    }

    // xóa vĩnh viễn
    public function forceDeleteGeneration($slug)
    {
        return DB::transaction(function () use ($slug) {
            $generation = Generation::where('slug', $slug)
                ->withTrashed()
                ->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->forceDelete();

            return $generation;
        });
    }

}
