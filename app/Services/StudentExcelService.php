<?php

namespace App\Services;

use App\Exports\StudentFormExport;
use App\Imports\StudentsImport;
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
        DB::transaction(function () use ($data) {
            $file = $data['file'];
            $generation_id = $data['generation_id'];
            $academic_year_id = $data['academic_year_id'];

            return Excel::import(new StudentsImport($generation_id, $academic_year_id), $file);
        });
    }

}