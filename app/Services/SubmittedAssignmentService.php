<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\SubmittedAssignment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SubmittedAssignmentService
{
    public function token()
    {
        $client_id = \Config('services.google.client_id');
        $client_secret = \Config('services.google.client_secret');
        $refresh_token = \Config('services.google.refresh_token');
        // $folder_id = \Config('services.google.folder_id');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        // dd($response->json());

        $accessToken = json_decode((string) $response->getBody(), true)['access_token'];
        // dd($accessToken);
        return $accessToken;
    }

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

            // Xử lý file upload
            if (isset($data['file_path'])) {
                $accessToken = $this->token();
                $client = new \GuzzleHttp\Client();

                // Tạo tên ngẫu nhiên cho file trước khi upload
                $fileName = 'submitted_assignment/' . Str::random(9) . $data['file_path']->getClientOriginalName();
                $mimeType = $data['file_path']->getClientMimeType();

                // Upload file lên Google Drive
                $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'multipart/related; boundary="foo_bar_baz"',
                    ],
                    'body' => implode("\r\n", [
                        '--foo_bar_baz',
                        'Content-Type: application/json; charset=UTF-8',
                        '',
                        json_encode([
                            'name' => $fileName,
                            'parents' => [config('services.google.folder_id')],
                            'mimeType' => $mimeType,
                        ]),
                        '--foo_bar_baz',
                        'Content-Type: ' . $mimeType,
                        'Content-Transfer-Encoding: base64',
                        '',
                        base64_encode(file_get_contents($data['file_path']->getRealPath())),
                        '--foo_bar_baz--',
                    ]),
                ]);

                if ($response->getStatusCode() == 200) {
                    $file_id = json_decode($response->getBody()->getContents())->id;
                    $data['file_path'] = $file_id;
                } else {
                    throw new Exception('Tải file mới lên Google Drive không thành công');
                }
            } else {
                throw new Exception('File nộp bài là bắt buộc');
            }

            // Tạo mới hoặc ghi đè bài nộp
            $data['submitted_at'] = now(); // Đánh dấu thời gian nộp bài
            return SubmittedAssignment::create($data); // Tạo bản ghi mới
        });
    }

    // Xem bài nộp của tất cả học sinh trong assignment thuộc lớp
    public function viewAllSubmittedAssignmentsByClass($classSlug, $assignmentSlug)
    {
        return DB::transaction(function () use ($classSlug, $assignmentSlug) {
            // Lấy thông tin lớp
            $class = Classes::where('slug', $classSlug)->first();
            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            // Lấy thông tin bài tập
            $assignment = Assignment::where('slug', $assignmentSlug)
                ->where('class_id', $class->id)
                ->first();

            if (!$assignment) {
                throw new Exception('Bài tập không tồn tại trong lớp đã chọn');
            }

            // Lấy danh sách bài nộp cho bài tập này từ các học sinh trong lớp
            $submittedAssignments = SubmittedAssignment::where('assignment_id', $assignment->id)
                ->whereIn('student_id', $class->students->pluck('id'))
                ->with(['student'])
                ->get();

            if ($submittedAssignments->isEmpty()) {
                throw new Exception('Không có bài nộp nào cho bài tập này');
            }

            return $submittedAssignments;
        });
    }

    // Xem bài nộp của một học sinh trong assignment thuộc lớp
    public function viewOneSubmittedAssignment($classSlug, $assignmentSlug, $username)
    {
        return DB::transaction(function () use ($classSlug, $assignmentSlug, $username) {
            // Lấy thông tin lớp
            $class = Classes::where('slug', $classSlug)->first();
            if (!$class) {
                throw new Exception('Lớp học không tồn tại hoặc đã bị xóa');
            }

            // Kiểm tra học sinh có thuộc lớp không dựa trên username
            $student = $class->students()->where('username', $username)->first();
            if (!$student) {
                throw new Exception('Học sinh không thuộc lớp này hoặc username không tồn tại');
            }

            // Lấy thông tin bài tập
            $assignment = Assignment::where('slug', $assignmentSlug)
                ->where('class_id', $class->id)
                ->first();

            if (!$assignment) {
                throw new Exception('Bài tập không tồn tại trong lớp đã chọn');
            }

            // Lấy bài nộp của học sinh
            $submittedAssignment = SubmittedAssignment::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->with('student')
                ->first();

            if (!$submittedAssignment) {
                throw new Exception('Học sinh chưa nộp bài cho bài tập này');
            }

            return $submittedAssignment;
        });
    }



    public function updateScoreAndFeedback($assignmentSlug, $score, $feedback, $username)
    {
        // Kiểm tra thông tin học sinh
        $student = User::where('username', $username)->first();
        if (!$student) {
            throw new Exception('Không tồn tại học sinh với username này.');
        }

        // Kiểm tra bài tập
        $assignment = Assignment::where('slug', $assignmentSlug)->first();
        if (!$assignment) {
            throw new Exception('Bài tập không tồn tại hoặc đã bị xóa.');
        }

        // Kiểm tra bài nộp
        $submittedAssignment = SubmittedAssignment::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$submittedAssignment) {
            throw new Exception('Bài nộp không tồn tại hoặc không thuộc về học sinh này.');
        }

        // Cập nhật điểm và phản hồi
        $submittedAssignment->score = $score;
        $submittedAssignment->feedback = $feedback;

        // Lưu vào cơ sở dữ liệu
        $submittedAssignment->save();

        return $submittedAssignment;
    }


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
