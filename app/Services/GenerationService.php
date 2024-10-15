<?php

namespace App\Services;
use App\Models\Generation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerationService
{
    public function createNewGeneration(array $data)
    {
        return DB::transaction(function () use ($data) {
            $generations = Generation::select('start_date', 'end_date')->get();

            // lấy năm bắt đầu và kết thúc nhập vào
            $startYear = Carbon::parse($data['start_date'])->year;
            $endYear = Carbon::parse($data['end_date'])->year;

            // tính tổng số năm của khóa học
            $generationTotalYear = $endYear - $startYear;

            // năm bắt đầu phải < năm kết thúc
            if ($startYear >= $endYear) {
                throw new Exception('Năm bắt đầu phải nhỏ hơn năm kết thúc');
            }

            if ($generationTotalYear > $data['year']) {
                throw new Exception('Khóa học chỉ có ' . $data['year'] . ' năm');
            }

            if ($generationTotalYear < $data['year']) {
                throw new Exception('Khóa học cần có ' . $data['year'] . ' năm');
            }

            // duyệt qua tất cả những khóa học sinh
            foreach ($generations as $generation) {
                $startGenerationYear = Carbon::parse($generation->start_date)->year;
                $endGenerationYear = Carbon::parse($generation->end_date)->year;

                // kiểm tra năm bắt đầu và năm kết thúc
                if ($startYear == $startGenerationYear || $endYear == $endGenerationYear) {
                    throw new Exception('Khóa học không thể cùng năm bắt đầu hoặc kết thúc với khóa học khác');
                }

                // kiểm tra chồng lấn thời gian
                if (
                    ($startYear < $endGenerationYear && $endYear > $startGenerationYear) ||
                    ($startGenerationYear < $endYear && $endGenerationYear > $startYear)
                ) {
                    throw new Exception('Khóa học sinh đã tồn tại trong khoảng thời gian này rồi');
                }

                // đảm bảo năm bắt đầu của khóa học mới phải lớn hơn năm kết thúc của các khóa trước
                if ($startYear < $endGenerationYear) {
                    throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn tất cả các năm kết thúc của các khóa trước');
                }
            }

            $data['slug'] = Str::slug($data['name']);

            // tạo mới khóa học
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

            $generations = Generation::select('id', 'name', 'slug', 'start_date', 'end_date')->get();

            $startYear = Carbon::parse($data['start_date'])->year;
            $endYear = Carbon::parse($data['end_date'])->year;

            $generationTotalYear = $endYear - $startYear;

            if ($startYear >= $endYear) {
                throw new Exception('Năm bắt đầu phải nhỏ hơn năm kết thúc');
            } else {
                if ($generationTotalYear > $data['year']) {
                    throw new Exception('Khóa học chỉ có ' . $data['year'] . ' năm');
                }
                if ($generationTotalYear < $data['year']) {
                    throw new Exception('Khóa học cần có ' . $data['year'] . ' năm');
                }
            }

            foreach ($generations as $generation) {
                if ($currentGeneration->id != $generation->id) {
                    $startGenerationYear = Carbon::parse($generation->start_date)->year;
                    $endGenerationYear = Carbon::parse($generation->end_date)->year;

                    if ($currentGeneration->start_date != $data['start_date'] || $currentGeneration->end_date != $data['end_date']) {
                        if ($startYear == $startGenerationYear || $endYear == $endGenerationYear) {
                            throw new Exception('Khóa học không thể cùng năm bắt đầu hoặc kết thúc với khóa học khác');
                        }

                        if (
                            ($startYear < $endGenerationYear && $endYear > $startGenerationYear) ||
                            ($startGenerationYear < $endYear && $endGenerationYear > $startYear)
                        ) {
                            throw new Exception('Khóa học sinh đã tồn tại trong khoảng thời gian này rồi');
                        }
                    }
                }
            }

            $data['slug'] = Str::slug($data['name']);

            // Cập nhật khóa học
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
