<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Semester;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SemesterService
{
    public function createNewSemester(array $data)
    {
        return DB::transaction(function () use ($data) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($startDate->gt($endDate)) {
                throw new Exception('Thời gian bắt đầu của kỳ học phải nhỏ hơn thời gian kết thúc');
            }

            $academicYear = AcademicYear::where('id', $data['academic_year_id'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            $startAcademicYear = Carbon::parse($academicYear->start_date);
            $endAcademicYear = Carbon::parse($academicYear->end_date);

            if ($startDate->lt($startAcademicYear) || $startDate->gt($endAcademicYear)) {
                throw new Exception('Thời gian bắt đầu kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->toDateString() . ' đến ' . $endAcademicYear->toDateString());
            }

            if ($endDate->lt($startAcademicYear) || $endDate->gt($endAcademicYear)) {
                throw new Exception('Thời gian kết thúc của kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->toDateString() . ' đến ' . $endAcademicYear->toDateString());
            }

            $monthsDiff = $startDate->diffInMonths($endDate);
            if ($monthsDiff > 5) {
                throw new Exception('Thời gian của kỳ học không được quá 5 tháng');
            }

            $previousSemester = Semester::where('academic_year_id', $data['academic_year_id'])
                ->orderBy('end_date', 'desc')
                ->first();

            if ($previousSemester) {
                $previousEndDate = Carbon::parse($previousSemester->end_date);

                if ($startDate->lt($previousEndDate)) {
                    throw new Exception('Thời gian bắt đầu của kỳ mới phải sau hoặc bằng thời gian kết thúc của kỳ học trước: ' . $previousEndDate->toDateString());
                }
            }

            $countYearOfSemester = Semester::where('academic_year_id', $data['academic_year_id'])->count();

            if ($countYearOfSemester >= 2) {
                throw new Exception('1 năm chỉ có ' . $countYearOfSemester . ' kỳ học');
            }

            $academicYearSlug = $academicYear->slug;
            $data['slug'] = Str::slug($data['name']);
            $data['slug'] = $academicYearSlug . '-' . $data['slug'] . '-' . rand(333, 999);

            return Semester::create($data);
        });
    }

    public function updateSemester(array $data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $semester = Semester::where('slug', $slug)->first();

            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại');
            }

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($startDate->gt($endDate)) {
                throw new Exception('Thời gian bắt đầu của kỳ học phải nhỏ hơn thời gian kết thúc');
            }

            $academicYear = AcademicYear::where('id', $data['academic_year_id'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            $startAcademicYear = Carbon::parse($academicYear->start_date);
            $endAcademicYear = Carbon::parse($academicYear->end_date);

            if ($startDate->lt($startAcademicYear) || $startDate->gt($endAcademicYear)) {
                throw new Exception('Thời gian bắt đầu kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->format('d/m/Y') . ' đến ' . $endAcademicYear->format('d/m/Y'));
            }

            if ($endDate->lt($startAcademicYear) || $endDate->gt($endAcademicYear)) {
                throw new Exception('Thời gian kết thúc kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->format('d/m/Y') . ' đến ' . $endAcademicYear->format('d/m/Y'));
            }

            $monthsDiff = $startDate->diffInMonths($endDate);
            if ($monthsDiff > 5) {
                throw new Exception('Thời gian của kỳ học không được quá 5 tháng.');
            }

            $previousSemester = Semester::where('academic_year_id', $data['academic_year_id'])
                ->where('id', '<', $semester->id)
                ->orderBy('end_date', 'desc')
                ->first();

            if ($previousSemester) {
                $previousEndDate = Carbon::parse($previousSemester->end_date);

                if ($startDate->lt($previousEndDate)) {
                    throw new Exception('Thời gian bắt đầu của kỳ mới phải sau hoặc bằng thời gian kết thúc của kỳ học trước: ' . $previousEndDate->format('d/m/Y'));
                }
            }

            $academicYearSlug = $academicYear->slug;
            $data['slug'] = Str::slug($data['name']);
            $data['slug'] = $academicYearSlug . '-' . $data['slug'] . '-' . rand(333, 999);

            $semester->update($data);
            return $semester;
        });
    }

    public function deleteSemester($slug)
    {
        return DB::transaction(function () use ($slug) {
            $semester = Semester::where('slug', $slug)->first();

            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại');
            }

            $semester->delete();
            return $semester;
        });
    }
}
