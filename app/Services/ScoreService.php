<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Score;
use App\Models\Subject;
use App\Models\User;
use App\Models\Semester;
use Exception;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    public function saveOrUpdateScore(array $data)
    {
        return DB::transaction(function () use ($data) {
            $student = User::where('username', $data['student_username'])->firstOrFail();
            $studentId = $student->id;

            $subject = Subject::where('slug', $data['subject_slug'])->firstOrFail();
            $subjectId = $subject->id;

            $class = Classes::where('slug', $data['class_slug'])->firstOrFail();
            $classId = $class->id;

            $semester = Semester::where('slug', $data['semester_slug'])->firstOrFail();
            $semesterId = $semester->id;

            $score = Score::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->where('semester_id', $semesterId)
                ->first();

            $averageScore = $this->calculateAverageScore($data['detailed_scores']);

            if ($score) {
                $score->update([
                    'detailed_scores' => $data['detailed_scores'],
                    'average_score' => $averageScore,
                ]);
            } else {
                $score = Score::create([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'class_id' => $classId,
                    'semester_id' => $semesterId,
                    'detailed_scores' => $data['detailed_scores'],
                    'average_score' => $averageScore,
                ]);
            }

            return $score;
        });
    }


    //Lấy điểm theo username -> subject_slug -> class_slug -> semester_slug
    public function getScoreByStudentSubjectClassSemester($student_name, $subject_slug, $class_slug, $semester_slug)
    {
        // Lấy user_id từ bảng user dựa trên usernmae
        $student = User::where('username', $student_name)->first();

        // Lấy subject_id từ bảng subject dựa trên slug
        $subject = Subject::where('slug', $subject_slug)->first();

        if (!$subject) {
            throw new Exception('Môn học không tồn tại.');
        }

        $class = Classes::where('slug', $class_slug)->first();
        if (!$class) {
            throw new Exception('Lớp học không tồn tại');
        }

        // Lấy semester_id từ bảng semester dựa trên slug
        $semester = Semester::where('slug', $semester_slug)->first();

        if (!$semester) {
            throw new Exception('Kỳ học không tồn tại.');
        }

        // Truy vấn điểm của học sinh theo student_id, subject_id, class_id, semester_id
        $score = Score::where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->first();

        if (!$score) {
            throw new Exception('Không tìm thấy điểm cho học sinh này trong môn học này.');
        }

        return $score;
    }


    // public function updateScore($id, array $data)
    // {
    //     // Lấy bản ghi Score dựa trên ID
    //     $score = Score::findOrFail($id);


    //     // Cập nhật các trường trong Score model
    //     $score->update([
    //         'detailed_scores' => $data['detailed_scores'],
    //     ]);

    //     // Cập nhật điểm trung bình nếu cần thiết
    //     $score->average_score = $this->calculateAverageScore($data['detailed_scores']);
    //     $score->save();

    //     return $score;
    // }

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

    private function calculateAverageScore(array $detailedScores)
    {
        $totalWeightedScore = 0;
        $totalWeights = 0;

        foreach ($detailedScores as $scoreType => $details) {
            if (isset($details['score'], $details['he_so']) && is_array($details['score'])) {
                $totalWeightedScore += array_sum($details['score']) * $details['he_so'];
                $totalWeights += count($details['score']) * $details['he_so'];
            }
        }
        return $totalWeights > 0 ? round($totalWeightedScore / $totalWeights, 2) : 0;
    }

}
