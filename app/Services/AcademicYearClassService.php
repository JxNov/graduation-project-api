<?php
namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AcademicYearClass;
use App\Models\Classes;
use Exception;
use Illuminate\Support\Facades\DB;

class AcademicYearClassService
{
    public function createNewAcademicYearClass($data)
    {
        return DB::transaction(function () use ($data) {
            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            $academicYearClassExists = AcademicYearClass::where('academic_year_id', $academicYear->id)
                ->where('class_id', $class->id)
                ->first();

            if ($academicYearClassExists) {
                throw new Exception('Lớp học đã tồn tại trong năm học này rồi');
            }

            $data['academic_year_id'] = $academicYear->id;
            $data['class_id'] = $class->id;

            $academicYearClass = AcademicYearClass::create($data);
            return $academicYearClass;
        });
    }

    public function updateAcademicYearClass($data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $academicYearClass = AcademicYearClass::find($id);

            $academicYear = AcademicYear::where('slug', $data['academic_year_slug'])->first();

            if ($academicYear === null) {
                throw new Exception('Năm học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            if (!$academicYearClass) {
                throw new Exception('Lớp học không tồn tại');
            }

            $academicYearClassExists = AcademicYearClass::where('academic_year_id', $academicYear->id)
                ->where('class_id', $class->id)
                ->where('id', '!=', $id)
                ->first();

            if ($academicYearClassExists) {
                throw new Exception('Lớp học đã tồn tại trong năm học này rồi');
            }

            $data['academic_year_id'] = $academicYear->id;
            $data['class_id'] = $class->id;

            $academicYearClass->update($data);

            return $academicYearClass;
        });
    }

    public function deleteAcademicYearClass($id)
    {
        return DB::transaction(function () use ($id) {
            $academicYearClass = AcademicYearClass::find($id);

            if ($academicYearClass === null) {
                throw new Exception('Không tìm thấy lớp học nào trong những năm học');
            }

            $academicYearClass->delete();

            return $academicYearClass;
        });
    }

    public function restoreAcademicYearClass($id)
    {
        return DB::transaction(function () use ($id) {
            $academicYearClass = AcademicYearClass::onlyTrashed()
                ->where('id', $id)
                ->first();

            if ($academicYearClass === null) {
                throw new Exception('Không tìm thấy lớp học của năm');
            }

            $academicYearClass->restore();
            return $academicYearClass;
        });
    }

    public function forceDeleteAcademicYearClass($id){
        return DB::transaction(function () use ($id) {
            $academicYearClass = AcademicYearClass::find($id);

            if ($academicYearClass === null) {
                throw new Exception('Không tìm thấy lớp học nào trong những năm học');
            }

            $academicYearClass->forceDelete();

            return $academicYearClass;
        });
    }
}