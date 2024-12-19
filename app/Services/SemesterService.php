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

            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $this->validateSemesterDates($startDate, $endDate, $academicYear);

            $durationInWeeks = $startDate->diffInMonths($endDate);
            if ($durationInWeeks < 3 || $durationInWeeks > 5) {
                throw new Exception('Thời gian của kỳ học phải từ 15 đến 20 tuần (khoảng 3.5 - 5 tháng)');
            }

            $previousSemester = Semester::where('academic_year_id', $academicYear->id)
                ->orderBy('end_date', 'desc')
                ->first();

            if ($previousSemester) {
                $previousEndDate = Carbon::parse($previousSemester->end_date);

                if ($startDate->lt($previousEndDate)) {
                    throw new Exception('Thời gian bắt đầu của kỳ mới phải sau hoặc bằng thời gian kết thúc của kỳ học trước: ' . $previousEndDate->toDateString());
                }
            }

            $countYearOfSemester = Semester::where('academic_year_id', $academicYear->id)->count();

            if ($countYearOfSemester >= 2) {
                throw new Exception('1 năm chỉ có ' . $countYearOfSemester . ' kỳ học');
            }

            $data['academic_year_id'] = $academicYear->id;
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

            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])
                ->select('id', 'name', 'slug', 'start_date', 'end_date')
                ->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $this->validateSemesterDates($startDate, $endDate, $academicYear);

            $durationInWeeks = $startDate->diffInMonths($endDate);
            if ($durationInWeeks < 3 || $durationInWeeks > 5) {
                throw new Exception('Thời gian của kỳ học phải từ 15 đến 20 tuần (khoảng 3.5 - 5 tháng)');
            }

            $previousSemester = Semester::where('academic_year_id', $academicYear->id)
                ->where('id', '<', $semester->id)
                ->orderBy('end_date', 'desc')
                ->first();

            if ($previousSemester) {
                $previousEndDate = Carbon::parse($previousSemester->end_date);

                if ($startDate->lt($previousEndDate)) {
                    throw new Exception('Thời gian bắt đầu của kỳ mới phải sau hoặc bằng thời gian kết thúc của kỳ học trước: ' . $previousEndDate->format('d/m/Y'));
                }
            }

            $data['academic_year_id'] = $academicYear->id;

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

    public function restoreSemester($slug)
    {
        return DB::transaction(function () use ($slug) {
            $semester = Semester::onlyTrashed()->where('slug', $slug)->first();

            if (!$semester) {
                throw new Exception('Kỳ học đã khôi phục hoặc không tồn tại');
            }

            $academicYear = AcademicYear::withTrashed()->where('id', $semester->academic_year_id);

            if ($academicYear === null) {
                throw new Exception('Cần khôi phục năm học của kỳ học trước');
            }

            if ($academicYear->trashed()) {
                throw new Exception('Cần khôi phục năm học của kỳ học trước');
            }

            $semester->restore();
            return $semester;
        });
    }

    public function forceDeleteSemester($slug)
    {
        return DB::transaction(function () use ($slug) {
            $semester = Semester::withTrashed()->where('slug', $slug)->first();

            if (!$semester) {
                throw new Exception('Kỳ học đã khôi phục hoặc không tồn tại');
            }

            $semester->forceDelete();
            return $semester;
        });
    }

    private function validateSemesterDates($startDate, $endDate, $academicYear)
    {
        $startAcademicYear = Carbon::parse($academicYear->start_date);
        $endAcademicYear = Carbon::parse($academicYear->end_date);

        if ($startDate->lt($startAcademicYear) || $startDate->gt($endAcademicYear)) {
            throw new Exception('Thời gian bắt đầu kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->toDateString() . ' đến ' . $endAcademicYear->toDateString());
        }

        if ($endDate->lt($startAcademicYear) || $endDate->gt($endAcademicYear)) {
            throw new Exception('Thời gian kết thúc kỳ học phải nằm trong khoảng từ ' . $startAcademicYear->toDateString() . ' đến ' . $endAcademicYear->toDateString());
        }
    }

}
