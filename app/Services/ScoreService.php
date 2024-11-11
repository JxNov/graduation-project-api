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

    public function updateScore(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $score = Score::find($id);

            if (!$score) {
                throw new Exception('Điểm không tồn tại.');
            }

            $data['average_score'] = $this->calculateAverageScore($data['detailed_scores']);

            $score->update($data);
            return $score;
        });
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
            return null;  // Thay vì 0, bạn có thể trả về null
        }

        $total = array_sum(array_column($detailedScores, 'score'));  // Sử dụng array_column để lấy tất cả điểm
        return round($total / count($detailedScores), 2);
    }


    public function getAverageScoreAttribute()
    {
        $scores = collect($this->detailed_scores)->pluck('score'); // Lấy tất cả giá trị 'score'

        if ($scores->isEmpty()) {
            return null; // Trả về null nếu không có điểm nào
        }

        return $scores->avg(); // Tính trung bình của các điểm số
    }

}
