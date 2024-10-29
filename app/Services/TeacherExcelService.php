<?php

namespace App\Services;

use App\Exports\TeacherFormExport;
use App\Imports\TeachersImport;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TeacherExcelService
{
    public function exportTeacherForm()
    {
        try {
            return Excel::download(new TeacherFormExport, 'teacher-form.xlsx');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function importTeacher($file)
    {
        return DB::transaction(function () use ($file) {
            return Excel::import(new TeachersImport(), $file);
        });
    }
}