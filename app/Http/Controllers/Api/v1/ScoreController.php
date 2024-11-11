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

    public function create()
    {
        $subjects = Subject::select('id', 'name')->latest('id')->get();
        $students = User::select('id', 'name')->latest('id')->get();
        $semesters = Semester::select('id', 'name')->latest('id')->get();

        if ($subjects->isEmpty() || $students->isEmpty() || $semesters->isEmpty()) {
            return $this->errorResponse('Không có dữ liệu', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            [
                'subjects' => $subjects,
                'students' => $students,
                'semesters' => $semesters
            ],
            'Lấy dữ liệu tạo điểm thành công',
            Response::HTTP_OK
        );
    }

    public function store(ScoreRequest $request)
    {
        try {
            $data = $request->validated();
            $score = $this->scoreService->createNewScore($data);

            return $this->successResponse(
                new ScoreResource($score),
                'Đã thêm điểm mới thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function edit($id)
    {
        $score = Score::find($id);
        $subjects = Subject::select('id', 'name')->latest('id')->get();
        $students = User::select('id', 'name')->latest('id')->get();
        $semesters = Semester::select('id', 'name')->latest('id')->get();

        if (!$score) {
            return $this->errorResponse('Điểm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(
            [
                new ScoreResource($score),
                'subjects' => $subjects,
                'students' => $students,
                'semesters' => $semesters
            ],
            'Lấy thông tin điểm thành công',
            Response::HTTP_OK
        );
    }

    //Show dựa trên id của bảng subject_score
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
    public function getScoreByStudentSubjectSemester($student_name, $subject_slug, $semester_slug)
    {
        try {
            // Gọi hàm từ ScoreService
            $score = $this->scoreService->getScoreByStudentSubjectSemester($student_name, $subject_slug, $semester_slug);

            return $this->successResponse(
                new ScoreResource($score),
                'Lấy điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


    public function update(ScoreRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $score = $this->scoreService->updateScore($data, $id);

            return $this->successResponse(
                new ScoreResource($score),
                'Đã cập nhật điểm thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    //Không nên destroy điểm hsinh
//    public function destroy($id)
//    {
//        try {
//            $this->scoreService->deleteScore($id);
//
//            return $this->successResponse(
//                null,
//                'Đã xóa điểm thành công',
//                Response::HTTP_OK
//            );
//        } catch (Exception $e) {
//            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
//        }
//    }

//    public function trash()
//    {
//        $scores = Score::onlyTrashed()
//            ->select('id', 'student_id', 'subject_id', 'semester_id', 'average_score')
//            ->latest('id')
//            ->paginate(6);
//
//        if ($scores->isEmpty()) {
//            return $this->successResponse(
//                null,
//                'Không có dữ liệu',
//                Response::HTTP_OK
//            );
//        }
//
//        return $this->successResponse(
//            new ScoreCollection($scores),
//            'Lấy tất cả thông tin điểm đã xóa thành công',
//            Response::HTTP_OK
//        );
//    }

//    public function restore($id)
//    {
//        try {
//            $score = $this->scoreService->restoreScore($id);
//
//            return $this->successResponse(
//                new ScoreResource($score),
//                'Đã khôi phục điểm thành công',
//                Response::HTTP_OK
//            );
//        } catch (Exception $e) {
//            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
//        }
//    }

//    public function forceDelete($id)
//    {
//        try {
//            $this->scoreService->forceDeleteScore($id);
//
//            return $this->successResponse(
//                null,
//                'Đã xóa vĩnh viễn điểm thành công',
//                Response::HTTP_OK
//            );
//        } catch (Exception $e) {
//            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
//        }
//    }
}
