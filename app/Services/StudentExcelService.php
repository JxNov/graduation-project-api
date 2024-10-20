<?php

namespace App\Services;

use App\Exports\StudentFormExport;
use Exception;
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
}