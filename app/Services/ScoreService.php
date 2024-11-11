<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Models\Semester;
use Exception;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    public function createNewScore(array $data)
    {
        return DB::transaction(function () use ($data) {
            $subject = Subject::find($data['subject_id']);
            $student = User::find($data['student_id']);
            $semester = Semester::find($data['semester_id']);

            if (!$subject) {
                throw new Exception('Môn học không tồn tại.');
            }

            if (!$student) {
                throw new Exception('Học sinh không tồn tại.');
            }

            if (!$semester) {
                throw new Exception('Kỳ học không tồn tại.');
            }

            $existingScore = Score::where('subject_id', $data['subject_id'])
                ->where('student_id', $data['student_id'])
                ->where('semester_id', $data['semester_id'])
                ->first();

            if ($existingScore) {
                throw new Exception('Điểm cho học sinh này trong môn học và kỳ học đã tồn tại.');
            }

            $data['average_score'] = $this->calculateAverageScore($data['detailed_scores']);

            return Score::create($data);
        });
    }

    //Lấy điểm theo username -> subject_slug -> semester_slug
    public function getScoreByStudentSubjectSemester($student_name, $subject_slug, $semester_slug)
    {
        // Lấy user_id từ bảng user dựa trên usernmae
        $student = User::where('username', $student_name)->first();

        // Lấy subject_id từ bảng subject dựa trên slug
        $subject = Subject::where('slug', $subject_slug)->first();

        if (!$subject) {
            throw new Exception('Môn học không tồn tại.');
        }

        // Lấy semester_id từ bảng semester dựa trên slug
        $semester = Semester::where('slug', $semester_slug)->first();

        if (!$semester) {
            throw new Exception('Kỳ học không tồn tại.');
        }

        // Truy vấn điểm của học sinh theo student_id, subject_id, semester_id
        $score = Score::where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->first();

        if (!$score) {
            throw new Exception('Không tìm thấy điểm cho học sinh này trong môn học này.');
        }

        if ($score->detailed_scores) {
            if (is_string($score->detailed_scores)) {
                // Nếu là chuỗi JSON, giải mã nó
                $score->detailed_scores = json_decode($score->detailed_scores, true);
            } elseif (is_array($score->detailed_scores)) {
                // Nếu là mảng, giữ nguyên
                $score->detailed_scores = $score->detailed_scores;
            } else {
                // Nếu không phải chuỗi JSON hay mảng, xử lý theo yêu cầu của bạn
                throw new Exception('detailed_scores có dữ liệu không hợp lệ.');
            }
        }

        return $score;
    }

//    public function updateScore(array $data, $id)
//    {
//        return DB::transaction(function () use ($data, $id) {
//            $score = Score::find($id);
//
//            if (!$score) {
//                throw new Exception('Điểm không tồn tại.');
//            }
//
//            $data['average_score'] = $this->calculateAverageScore($data['detailed_scores']);
//
//            $score->update($data);
//            return $score;
//        });
//    }

    public function updateScore($data, $score_id)
    {
        // Tìm điểm theo ID
        $score = Score::find($score_id);

        if (!$score) {
            throw new Exception('Điểm không tồn tại.');
        }

        //$score->student_id = $data['student_id'];
        //$score->subject_id = $data['subject_id'];
        //$score->semester_id = $data['semester_id'];
        $score->detailed_scores = json_encode($data['detailed_scores']);
        $score->average_score = $this->calculateAverageScore($data['detailed_scores']);
        $score->save();

        return $score;
    }

    public function deleteScore($id)
    {
        return DB::transaction(function () use ($id) {
            $score = Score::find($id);

            if (!$score) {
                throw new Exception('Điểm không tồn tại.');
            }

            $score->delete();
            return $score;
        });
    }

    public function restoreScore($id)
    {
        return DB::transaction(function () use ($id) {
            $score = Score::onlyTrashed()->find($id);

            if (!$score) {
                throw new Exception('Điểm đã khôi phục hoặc không tồn tại.');
            }

            $score->restore();
            return $score;
        });
    }

    public function forceDeleteScore($id)
    {
        return DB::transaction(function () use ($id) {
            $score = Score::withTrashed()->find($id);

            if (!$score) {
                throw new Exception('Điểm đã khôi phục hoặc không tồn tại.');
            }

            $score->forceDelete();
            return $score;
        });
    }

//    private function calculateAverageScore(array $detailedScores)
//    {
//        if (empty($detailedScores)) {
//            return 0;
//        }
//
//        $total = array_sum($detailedScores);
//        return round($total / count($detailedScores), 2);
//    }
    private function calculateAverageScore(array $detailedScores): ?float
    {
        if (empty($detailedScores)) {
            return null;
        }

        $total = array_sum(array_column($detailedScores, 'score'));  // Sử dụng array_column để lấy tất cả điểm
        return round($total / count($detailedScores), 2);
    }


}
