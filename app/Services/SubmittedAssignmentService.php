<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\SubmittedAssignment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmittedAssignmentService
{
    public function createOrUpdateSubmittedAssignment($data)
    {
        return DB::transaction(function () use ($data) {
            // Kiểm tra bài tập
            $assignment = Assignment::find($data['assignment_id']);
            if ($assignment === null) {
                throw new Exception('Bài tập không tồn tại hoặc đã bị xóa');
            }

            // Kiểm tra sinh viên
            $student = User::where('username', $data['student_username'])->first();
            if ($student === null) {
                throw new Exception('Sinh viên không tồn tại hoặc đã bị xóa');
            }

            $data['student_id'] = $student->id;

            // Kiểm tra xem sinh viên đã nộp bài cho bài tập này chưa
            $existingSubmission = SubmittedAssignment::where('assignment_id', $data['assignment_id'])
                ->where('student_id', $student->id)
                ->first();

            // Nếu đã tồn tại bài nộp, xóa file cũ trước khi cập nhật
            if ($existingSubmission) {
                $this->deleteFileFromFirebase($existingSubmission->file_path);
                $existingSubmission->delete(); // Xóa bản ghi cũ trước khi tạo bản ghi mới
            }

            // Xử lý file upload
            if (isset($data['file_path'])) {
                $firebase = app('firebase.storage');
                $storage = $firebase->getBucket();

                // Tạo tên ngẫu nhiên cho file trước khi upload
                $firebasePath = 'submitted_assignment/' . Str::random(9) . $data['file_path']->getClientOriginalName();

                // Upload file lên Firebase Storage
                $storage->upload(
                    file_get_contents($data['file_path']->getRealPath()),
                    [
                        'name' => $firebasePath
                    ]
                );

                // Cập nhật đường dẫn file trong dữ liệu
                $data['file_path'] = $firebasePath;
            } else {
                throw new Exception('File nộp bài là bắt buộc');
            }

            // Tạo mới hoặc ghi đè bài nộp
            $data['submitted_at'] = now(); // Đánh dấu thời gian nộp bài
            return SubmittedAssignment::create($data); // Tạo bản ghi mới
        });
    }

    //Hàm chỉ cho phép giáo viên sửa điểm và feedback
    public function updateScoreAndFeedback($assignmentId, $score, $feedback, $username)
    {
        // Kiểm tra xem người dùng có phải là giáo viên không
        $user = User::where('username', $username)->first();
        if (!$user)
        {
            throw new Exception('Không tồn tại người dùng');
        }

        $isTeacher = $user->roles()->where('slug', 'teacher')->exists();
        if (!$isTeacher)
        {
            throw new Exception('Người dùng không phải giáo viên');
        }

        // Kiểm tra bài nộp của sinh viên
        $submittedAssignment = SubmittedAssignment::where('assignment_id', $assignmentId)
            ->where('student_id', $user->username)
            ->first();

        if (!$submittedAssignment) {
            throw new Exception('Bài nộp không tồn tại hoặc không thuộc về sinh viên này.');
        }

        // Cập nhật điểm và phản hồi
        $submittedAssignment->score = $score;
        $submittedAssignment->feedback = $feedback;

        // Lưu các thay đổi vào cơ sở dữ liệu
        $submittedAssignment->save();

        return $submittedAssignment;
    }

    // Hàm xóa file từ Firebase Storage
    private function deleteFileFromFirebase($filePath)
    {
        if ($filePath) {
            $firebase = app('firebase.storage');
            $storage = $firebase->getBucket();
            $file = $storage->object($filePath);

            // Kiểm tra xem file có tồn tại trong Firebase không trước khi xóa
            if ($file->exists()) {
                $file->delete();
            }
        }
    }
}
