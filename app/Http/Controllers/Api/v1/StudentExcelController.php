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
            $data = $request->all();

            $this->studentExcelService->importStudents($data);

            return $this->successResponse(
                [],
                'Danh sách học sinh đang được nhập, hãy đợi chút',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function exportStudentByGeneration($slug)
    {
        try {
            return $this->studentExcelService->exportStudentByGeneration($slug);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function exportStudentByAcademicYear($slug)
    {
        try {
            return $this->studentExcelService->exportStudentByAcademicYear($slug);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
