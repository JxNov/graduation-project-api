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

    public function importStudents($file)
    {
        DB::transaction(function () use ($file) {
            return Excel::import(new StudentsImport, $file);
        });
    }
}