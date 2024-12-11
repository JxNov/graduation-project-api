<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Generation;
use App\Models\Semester;
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

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($startDate->diffInMonths($endDate) != 48) {
                throw new Exception('Khóa học cần có độ dài 48 tháng');
            }

            if ($generations->isNotEmpty()) {
                foreach ($generations as $generation) {
                    $existingStart = Carbon::parse($generation->start_date);

                    if ($startDate->year <= $existingStart->year) {
                        throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn năm bắt đầu của khóa học trước');
                    }
                }
            }

            $data['slug'] = Str::slug($data['name']);

            $newGeneration = Generation::create($data);

            $this->createAcademicYearAndSemesterWhenCreateNewGeneration($newGeneration, $startDate, $endDate);

            return $newGeneration;
        });
    }

    public function updateGeneration(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $currentGeneration = Generation::where('slug', $slug)->first();
            $oldStartDate = $currentGeneration->start_date;
            $oldEndDate = $currentGeneration->end_date;

            if ($currentGeneration === null) {
                throw new Exception('Không tìm thấy khóa học này');
            }

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($startDate->diffInMonths($endDate) != 48) {
                throw new Exception('Khóa học cần có độ dài 48 tháng');
            }

            $generations = Generation::where('id', '<', $currentGeneration->id)
                ->select('start_date')
                ->get();

            if ($generations->isNotEmpty()) {
                foreach ($generations as $generation) {
                    $existingStart = Carbon::parse($generation->start_date);

                    if ($startDate->year <= $existingStart->year) {
                        throw new Exception('Năm bắt đầu của khóa học mới phải lớn hơn năm bắt đầu của khóa học trước');
                    }
                }
            }

            $currentGeneration->update($data);

            if ($oldStartDate !== $data['start_date'] || $oldEndDate !== $data['end_date']) {
                $this->createAcademicYearAndSemesterWhenCreateNewGeneration($currentGeneration, $startDate, $endDate);
            }

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

    private function createAcademicYearAndSemesterWhenCreateNewGeneration($generation, $startDate, $endDate)
    {
        $academicYears = [];
        $currentStartDate = Carbon::parse($startDate);

        while (true) {
            $nextStartDate = $currentStartDate->copy()->addMonths(8)->day(30);

            if ($nextStartDate->gt($endDate)) {
                if ($currentStartDate->diffInMonths($endDate) >= 10) {
                    $academicYears[] = [
                        'name' => "{$currentStartDate->year}-{$endDate->year}",
                        'slug' => Str::slug("{$generation->slug}-{$currentStartDate->year}-{$endDate->year}"),
                        'start_date' => $currentStartDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'generation_id' => $generation->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                break;
            }

            $academicYears[] = [
                'name' => "{$currentStartDate->year}-{$nextStartDate->year}",
                'slug' => Str::slug("{$generation->slug}-{$currentStartDate->year}-{$nextStartDate->year}"),
                'start_date' => $currentStartDate->toDateString(),
                'end_date' => $nextStartDate->toDateString(),
                'generation_id' => $generation->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentStartDate = $nextStartDate->copy()->addMonths(4);
        }

        AcademicYear::where('generation_id', $generation->id)->forceDelete();
        AcademicYear::insert($academicYears);

        $newAcademicYears = AcademicYear::orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        foreach ($newAcademicYears as $academicYear) {
            for ($i = 1; $i <= 2; $i++) {
                $semesterData = [
                    'name' => 'Kỳ ' . $i,
                    'slug' => Str::slug("{$academicYear['slug']}-Kỳ-$i-" . rand(333, 999)),
                    'start_date' => $i == 1 ? $academicYear['start_date'] : Carbon::parse($academicYear['start_date'])->addMonths(4)->addDays(3)->toDateString(),
                    'end_date' => $i == 1 ? Carbon::parse($academicYear['start_date'])->addMonths(4)->toDateString() : $academicYear['end_date'],
                    'academic_year_id' => $academicYear['id'],
                ];

                Semester::create($semesterData);
            }
        }
    }
}
