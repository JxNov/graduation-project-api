<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Generation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicYearService
{
    public function createNewAcademicYear(array $data)
    {
        return DB::transaction(function () use ($data) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            $generationTotalYear = $endDate->year - $startDate->year;

            // năm bắt đầu phải < năm kết thúc
            if ($startDate->gte($endDate)) {
                throw new Exception('Ngày bắt đầu phải nhỏ hơn ngày kết thúc');
            }

            $durationInMonths = $startDate->diffInMonths($endDate);
            if ($durationInMonths < 7 || $durationInMonths > 9) {
                throw new Exception('Thời gian của năm học năm trong khoảng từ 7 đến 9 tháng');
            }

            $generation = Generation::where('slug', $data['generation_slug'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $startGenerationDate = Carbon::parse($generation->start_date);
            $endGenerationDate = Carbon::parse($generation->end_date);

            // kiểm tra nếu ngày bắt đầu và ngày kết thúc nằm trong khoảng thời gian của khóa học
            if ($startDate->lt($startGenerationDate) || $startDate->gt($endGenerationDate)) {
                throw new Exception('Ngày bắt đầu phải nằm trong khoảng: ' . $startGenerationDate->toDateString() . ' đến: ' . $endGenerationDate->toDateString());
            }

            if ($endDate->lt($startGenerationDate) || $endDate->gt($endGenerationDate)) {
                throw new Exception('Ngày kết thúc phải nằm trong khoảng: ' . $startGenerationDate->toDateString() . ' đến: ' . $endGenerationDate->toDateString());
            }

            $countYearOfGeneration = AcademicYear::where('generation_id', $generation->id)->count();

            if ($countYearOfGeneration && $countYearOfGeneration >= 4) {
                throw new Exception('Khóa học đã có đủ: ' . $countYearOfGeneration . ' năm học');
            }

            if ($countYearOfGeneration) {
                $lastAcademicYear = AcademicYear::where('generation_id', $generation->id)
                    ->select('id', 'end_date', 'generation_id')
                    ->orderBy('end_date', 'desc')
                    ->first();

                if (!$lastAcademicYear) {
                    throw new Exception('Chưa có năm học nào cho khóa học này');
                }

                if ($lastAcademicYear && $startDate->lt(Carbon::parse($lastAcademicYear->end_date))) {
                    throw new Exception('Ngày bắt đầu của năm học sau phải lớn hơn hoặc bằng ngày kết thúc của năm học trước');
                }
            }

            $data['generation_id'] = $generation->id;
            $generationSlug = Str::slug($generation->slug);
            $data['slug'] = Str::slug($data['name']);
            $data['slug'] = $generationSlug . '-' . $data['slug'];

            return AcademicYear::create($data);
        });
    }

    public function updateAcademicYear(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $academicYear = AcademicYear::where('slug', $slug)->first();
            if (!$academicYear) {
                throw new Exception('Năm học không tồn tại');
            }

            $generation = Generation::where('slug', $data['generation_slug'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            $startYear = $startDate->year;
            $endYear = $endDate->year;

            if ($startYear >= $endYear) {
                throw new Exception('Năm bắt đầu phải nhỏ hơn năm kết thúc');
            }

            $durationInMonths = $startDate->diffInMonths($endDate);
            if ($durationInMonths < 7 || $durationInMonths > 9) {
                throw new Exception('Thời gian của năm học năm trong khoảng từ 7 đến 9 tháng');
            }

            $startGenerationDate = Carbon::parse($generation->start_date);
            $endGenerationDate = Carbon::parse($generation->end_date);

            if ($startDate->lt($startGenerationDate) || $startDate->gt($endGenerationDate)) {
                throw new Exception('Ngày bắt đầu phải nằm trong khoảng: ' . $startGenerationDate->toDateString() . ' đến: ' . $endGenerationDate->toDateString());
            }

            if ($endDate->lt($startGenerationDate) || $endDate->gt($endGenerationDate)) {
                throw new Exception('Ngày kết thúc phải nằm trong khoảng: ' . $startGenerationDate->toDateString() . ' đến: ' . $endGenerationDate->toDateString());
            }

            // lấy những năm học trước năm học hiện tại để so sánh
            $lastAcademicYear = AcademicYear::where('generation_id', $generation->id)
                ->select('id', 'end_date', 'generation_id')
                ->where('id', '<', $academicYear->id)
                ->orderBy('end_date', 'desc')
                ->first();

            if ($lastAcademicYear) {
                $lastYearOfAcademicYear = Carbon::parse($lastAcademicYear->end_date);

                if ($startDate->lt($lastYearOfAcademicYear)) {
                    throw new Exception('Năm bắt đầu của khóa học sau phải lớn hơn hoặc bằng năm kết thúc của khóa học trước.');
                }
            }

            $data['generation_id'] = $generation->id;

            $academicYear->update($data);
            return $academicYear;
        });
    }

    public function deleteAcademicYear($slug)
    {
        return DB::transaction(function () use ($slug) {
            $academicYear = AcademicYear::where('slug', $slug)->first();

            if ($academicYear === null) {
                throw new Exception('Không tìm thấy năm học');
            }

            $academicYear->delete();

            return $academicYear;
        });
    }

    public function restoreAcademicYear($slug)
    {
        return DB::transaction(function () use ($slug) {
            $academicYear = AcademicYear::where('slug', $slug)
                ->onlyTrashed()
                ->first();

            if ($academicYear === null) {
                throw new Exception('Không tìm thấy năm học đã xóa');
            }

            $academicYear->restore();

            return $academicYear;
        });
    }

    public function forceDeleteAcademicYear($slug)
    {
        return DB::transaction(function () use ($slug) {
            $academicYear = AcademicYear::where('slug', $slug)
                ->withTrashed()
                ->first();

            if ($academicYear === null) {
                throw new Exception('Không tìm thấy năm học đã xóa');
            }

            $academicYear->forceDelete();

            return $academicYear;
        });
    }
}
