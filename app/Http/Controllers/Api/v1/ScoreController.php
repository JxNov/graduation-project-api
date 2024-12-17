<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScoreRequest;
use App\Http\Resources\ScoreCollection;
use App\Http\Resources\ScoreResource;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Models\Semester;
use App\Services\ScoreService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;

class ScoreController extends Controller
{
    use ApiResponseTrait;

    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    public function index()
    {
        $scores = Score::select('id', 'student_id', 'subject_id', 'semester_id', 'average_score')
            ->latest('id')
            ->paginate(6);

        if ($scores->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new ScoreCollection($scores),
            'Lấy tất cả thông tin điểm thành công',
            Response::HTTP_OK
        );
    }

    public function store(ScoreRequest $request)
    {
        try {
            $data = $request->validated();

            $score = $this->scoreService->saveOrUpdateScore($data);

            return $this->successResponse(
                new ScoreResource($score),
                'Lưu điểm cho học sinh thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show($id)
    {
        $score = Score::find($id);

        if (!$score) {
            return $this->errorResponse('Điểm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            new ScoreResource($score),
            'Lấy thông tin điểm thành công',
            Response::HTTP_OK
        );
    }

    //Truy vấn dựa trên username, id_subject và theo id_semester
    public function getScoreByStudentSubjectSemester($student_name, $subject_slug, $class_slug, $semester_slug)
    {
        try {
            // Gọi hàm từ ScoreService
            $score = $this->scoreService->getScoreByStudentSubjectClassSemester($student_name, $subject_slug, $class_slug, $semester_slug);

            return $this->successResponse(
                new ScoreResource($score),
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


    // public function update(ScoreRequest $request, $id)
    // {
    //     try {
    //         $data = $request->validated();

    //         $score = $this->scoreService->updateScore($id, $data);

    //         return $this->successResponse(
    //             new ScoreResource($score),
    //             'Cập nhật điểm thành công',
    //             Response::HTTP_OK
    //         );
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }
}
