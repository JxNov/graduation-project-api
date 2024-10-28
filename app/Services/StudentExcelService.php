<?php

namespace App\Services;

use App\Exports\DataStudentByGenerationExport;
use App\Exports\StudentFormExport;
use App\Imports\StudentsImport;
use App\Models\Generation;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentExcelService
{
    public function exportStudentForm()
    {
        try {
            return Excel::download(new StudentFormExport, 'student-form.xlsx');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function importStudents(array $data)
    {
        return DB::transaction(function () use ($data) {
            $file = $data['file'];
            $generationSlug = $data['generationSlug'];
            $academicYearSlug = $data['academicYearSlug'];

            return Excel::import(new StudentsImport($generationSlug, $academicYearSlug), $file);
        });
    }

    public function exportStudentByGeneration($slug)
    {
        try {
            $generation = Generation::where('slug', $slug)->select('slug', 'name')->first();

            if ($generation === null) {
                throw new Exception('Không tìm thấy khóa học');
            }

            return Excel::download(new DataStudentByGenerationExport($slug), 'Danh sách học sinh khóa: ' . $generation->name . '.xlsx');
        } catch (Exception $e) {
            throw $e;
        }
    }
}