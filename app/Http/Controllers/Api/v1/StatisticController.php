<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatisticResource;
use App\Services\StatisticService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\ScoreResource;
use App\Models\Classes;
use App\Models\Generation;
use App\Models\User;

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

    public function getStatisticByClassSemester($class_slug, $semester_slug)
    {
        try {
            $statistic = $this->statisticService->getStatisticByClassSemester($class_slug, $semester_slug);

            return $this->successResponse(
                $statistic,
                'Thống kê điểm của lớp theo kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getStatisticAllClassInSemester($semester_slug)
    {
        try {
            $statistic = $this->statisticService->getStatisticAllClassInSemester($semester_slug);

            return $this->successResponse(
                $statistic,
                'Thống kê điểm của tất cả lớp theo kỳ học thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function countStudentInBlockByAcademicYear()
    {
        try {
            $count = $this->statisticService->countStudentInBlockByAcademicYear();

            return $this->successResponse(
                $count,
                'Thống kê số học sinh trong khối của năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function StudentClassInBlock($block_slug)
    {
        try {
            $count = $this->statisticService->countStudentsInBlock($block_slug);

            return $this->successResponse(
                $count,
                'Thống kê số học sinh trong khối thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function getGenderRatioInGeneration()
    {
        try {
            $count = $this->statisticService->getGenderRatioInGeneration();

            return $this->successResponse(
                $count,
                'Thống kê số học sinh nam, nữ trong khoá thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function showStudentScoreSemester($classSlug, $semesterSlug, $yearSlug)
    {
        try {
            $student = $this->statisticService->showStudentScoreSemester($classSlug, $semesterSlug, $yearSlug);

            return $this->successResponse(
                $student,
                'Lấy thông tin điểm các môn học của học sinh thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function showStudentScoreSemesterClass($classSlug, $semesterSlug, $yearSlug)
    {
        try {
            $student = $this->statisticService->showStudentScoreSemesterClass($classSlug, $semesterSlug, $yearSlug);

            return $this->successResponse(
                $student,
                'Lấy thông tin điểm các môn học của học sinh trong lớp thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function calculateFinalScoreYearClass($classSlug, $yearSlug)
    {
        try {
            $student = $this->statisticService->calculateFinalScoreYearClass($classSlug, $yearSlug);

            return $this->successResponse(
                $student,
                'Success!',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function countAll()
    {
        try {
            $students = User::whereHas('roles', function ($query) {
                $query->where('slug', 'student');
            })->whereNull('deleted_at')->count();
            $teachers = User::whereHas('roles', function ($query) {
                $query->where('slug', 'teacher');
            })->whereNull('deleted_at')->count();
            $generation = Generation::count();
            $classes = Classes::count();
            $count = [
                'numberStudent' => $students,
                'numberTeacher' => $teachers,
                'numberGeneration' => $generation,
                'numberClasses' => $classes
            ];
            return $this->successResponse(
                $count,
                'Thống kê số học sinh toàn trường thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function getPerformationLevelAll($academicYearSlug){
        try {
            $performation = $this->statisticService->getPerformationLevelAll($academicYearSlug);
            return $this->successResponse(
                $performation,
                'Thống kê học lực toàn trường trong năm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
