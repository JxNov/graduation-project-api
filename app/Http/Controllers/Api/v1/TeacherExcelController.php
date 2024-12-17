<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherImportRequest;
use App\Services\TeacherExcelService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class TeacherExcelController extends Controller
{
    use ApiResponseTrait;
    protected $teacherExcelService;

    public function __construct(TeacherExcelService $teacherExcelService)
    {
        $this->teacherExcelService = $teacherExcelService;
    }

    public function exportTeacherForm()
    {
        try {
            return $this->teacherExcelService->exportTeacherForm();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function importTeacher(TeacherImportRequest $request)
    {
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
            }

            $this->teacherExcelService->importTeacher($file);

            return $this->successResponse(
                [],
                'Danh sách giáo viên đang được nhập, hãy đợi chút',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
