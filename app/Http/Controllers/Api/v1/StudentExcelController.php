<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentImportRequest;
use App\Services\StudentExcelService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class StudentExcelController extends Controller
{
    use ApiResponseTrait;
    protected $studentExcelService;

    public function __construct(StudentExcelService $studentExcelService)
    {
        $this->studentExcelService = $studentExcelService;
    }

    public function exportStudentForm()
    {
        try {
            return $this->studentExcelService->exportStudentForm();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function importStudent(StudentImportRequest $request)
    {
        try {

            if ($request->hasFile('file')) {
                $file = $request->file('file');
            }

            $this->studentExcelService->importStudents($file);

            return $this->successResponse(
                [],
                'Đã nhập danh sách học sinh thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
