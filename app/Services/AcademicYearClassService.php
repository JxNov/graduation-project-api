<?php
namespace App\Services;

use App\Models\AcademicYearClass;
use Exception;
use Illuminate\Support\Facades\DB;

class AcademicYearClassService
{
    public function createNewAcademicYearClass($data)
    {
        DB::transaction(function () use ($data) {
            $academicYearClassExists = AcademicYearClass::where('academic_year_id', $data['academic_year_id'])
                ->where('class_id', $data['class_id'])
                ->first();

            if ($academicYearClassExists) {
                throw new Exception('Lớp học đã tồn tại trong 1 năm học khác');
            }

            $academicYearClass = AcademicYearClass::create($data);
            return $academicYearClass;
        });
    }

    public function updateAcademicYearClass($data, $id)
    {
        DB::transaction(function () use ($data, $id) {
            $academicYearClass = AcademicYearClass::find($id);

            if (!$academicYearClass) {
                throw new Exception('Lớp học không tồn tại');
            }

            $academicYearClassExists = AcademicYearClass::where('academic_year_id', $data['academic_year_id'])
                ->where('class_id', $data['class_id'])
                ->where('id', '!=', $id)
                ->first();

            if ($academicYearClassExists) {
                throw new Exception('Lớp học đã tồn tại trong 1 năm học khác');
            }

            $academicYearClass->update($data);

            return $academicYearClass;
        });
    }

    public function deleteAcademicYearClass($id)
    {
        DB::transaction(function () use ($id) {
            $academicYearClass = AcademicYearClass::find($id);

            if ($academicYearClass === null) {
                throw new Exception('Không tìm thấy lớp học nào trong những năm học');
            }

            $academicYearClass->delete();

            return $academicYearClass;
        });
    }
}