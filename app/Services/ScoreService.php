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
    public function createNewScore($studentName, $subjectSlug, $classSlug, $semesterSlug, array $detailedScores)
    {
        // Lấy user_id dựa trên username
        $student = User::where('username', $studentName)->firstOrFail();
        $studentId = $student->id;

        // Lấy subject_id dựa trên subject_slug
        $subject = Subject::where('slug', $subjectSlug)->firstOrFail();
        $subjectId = $subject->id;

        //Lấy class_id dựa trên class_slug
        $class = Classes::where('slug', $classSlug)->firstOrFail();
        $classId = $class->id;

        // Lấy semester_id dựa trên semester_slug
        $semester = Semester::where('slug', $semesterSlug)->firstOrFail();
        $semesterId = $semester->id;

        // Tính toán điểm trung bình từ detailed_scores (tính trung bình từ mảng điểm)
        $averageScore = $this->calculateAverageScore($detailedScores);

        // Tạo điểm mới
        return Score::create([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'detailed_scores' => $detailedScores,
            'average_score' => $averageScore,
        ]);
    }

    //Lấy điểm theo username -> subject_slug -> class_slug -> semester_slug
    public function getScoreByStudentSubjectSemester($student_name, $subject_slug, $class_slug, $semester_slug)
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


    public function updateScore($id, array $data)
    {
        // Lấy bản ghi Score dựa trên ID
        $score = Score::findOrFail($id);


        // Cập nhật các trường trong Score model
        $score->update([
            'detailed_scores' => $data['detailed_scores'],
        ]);

        // Cập nhật điểm trung bình nếu cần thiết
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

    private function calculateAverageScore(array $detailedScores)
    {
        $totalScore = 0;
        $count = 0;

        // Tính tổng điểm từ các mảng trong detailed_scores
        foreach ($detailedScores as $scores) {
            if (is_array($scores)) {
                $totalScore += array_sum($scores);
                $count += count($scores);
            }
        }
        return $count > 0 ? round($totalScore / $count, 2) : 0;
    }
}
