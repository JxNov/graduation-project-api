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
        DB::beginTransaction();

        try {
            $generations = Generation::select('start_date', 'end_date')->get();

            // lấy năm bắt đầu và kết thúc nhập vào
            $startYear = Carbon::parse($data['start_date'])->year;
            $endYear = Carbon::parse($data['end_date'])->year;

            // Log::info('startYear: ' . $startYear . ' endYear: ' . $endYear);

            // tính tổng số năm của khóa học
            $generationTotalYear = $endYear - $startYear;

            // năm bắt đầu phải < năm kết thúc
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

            // duyệt qua tất cả những khóa học sinh
            foreach ($generations as $generation) {
                // lấy năm bắt đầu và kết thúc của tất cả khóa học sinh

                $startGenerationYear = Carbon::parse($generation->start_date)->year;
                $endGenerationYear = Carbon::parse($generation->end_date)->year;

                // kiểm tra năm bắt đầu có trùng với năm bắt đầu và kết thúc có trùng năm kết thúc không
                if ($startYear == $startGenerationYear || $endYear == $endGenerationYear) {
                    throw new Exception('Khóa học không thể cùng năm bắt đầu hoặc kết thúc với khóa học khác');
                }

                // kiểm tra xem khoảng thời gian của khóa học mới có bị chồng lấn với khóa học hiện có hay không
                if (
                    ($startYear < $endGenerationYear && $endYear > $startGenerationYear) ||
                    ($startGenerationYear < $endYear && $endGenerationYear > $startYear)
                ) {
                    throw new Exception('Khóa học sinh đã tồn tại trong khoảng thời gian này rồi');
                }

                // đảm bảo, năm bắt đầu của khóa học mới phải luôn >= năm kết thúc của những năm trước
                // tháng 5 học xong tháng 9 khai giảng, tức có thể = năm kết thúc
                if ($startYear < $endGenerationYear) {
                    throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn tất cả các năm kết thúc của các khóa trước');
                }
            }

            $data['slug'] = Str::slug($data['name']);

            // tạo mới khóa học
            $newGeneration = Generation::create($data);

            DB::commit();

            return $newGeneration;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function updateGeneration(array $data, $slug)
    {
        DB::beginTransaction();

        try {
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

            // Chỉ kiểm tra sự trùng lặp năm nếu có khóa học khác
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

            DB::commit();

            return $currentGeneration;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteGeneration($slug)
    {
        try {
            $generation = Generation::where('slug', $slug)->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->delete();

            return $generation;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function restoreGeneration($slug)
    {
        DB::beginTransaction();
        try {
            $generation = Generation::onlyTrashed()->where('slug', $slug)->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->restore();

            DB::commit();
            return $generation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // xóa vĩnh viễn
    public function forceDeleteGeneration($slug)
    {
        try {
            $generation = Generation::where('slug', $slug)
                ->withTrashed()
                ->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $generation->forceDelete();

            return $generation;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
