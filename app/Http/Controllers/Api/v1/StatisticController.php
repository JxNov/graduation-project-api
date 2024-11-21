<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatisticResource;
use App\Services\StatisticService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatisticController extends Controller
{
    use ApiResponseTrait;

    protected $statisticService;

    public function __construct(StatisticService $statisticService)
    {
        $this->statisticService = $statisticService;
    }

    // StatisticController.php
    public function getStatisticByClassSubjectSemester($subject_slug, $class_slug, $semester_slug)
    {
        try {
            // Lấy dữ liệu thống kê từ StatisticService
            $statistic = $this->statisticService->getStatisticByClassSubjectSemester(
                $subject_slug,
                $class_slug,
                $semester_slug
            );

            // Trả về thông tin thống kê
            return $this->successResponse(
                new StatisticResource($statistic),
                'Thống kê lớp học và môn học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getStatisticBySemester($class_slug, $semester_slug)
    {
        try {
            $statistic = $this->statisticService->getStatisticBySemester($class_slug, $semester_slug);

            return $this->successResponse(
                $statistic,
                'Thống kê theo kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function countStudentInBlockByAcademicYear($academic_year_slug)
    {
        try {
            $count = $this->statisticService->countStudentInBlockByAcademicYear($academic_year_slug);

            return $this->successResponse(
                $count,
                'Thống kê số học sinh trong khối của năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
