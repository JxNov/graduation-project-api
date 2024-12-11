<?php

namespace App\Http\Requests;

use App\Models\SubmittedAssignment;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Foundation\Http\FormRequest;
use Exception;

class SubmittedAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return $this->rulesForCreate();
        }
        elseif ($this->isMethod('patch') || $this->isMethod('put')) {
            return $this->rulesForUpdate();
        }

        return [];
    }

    public function rulesForCreate(): array
    {
        return [
            'student_username' => 'required|exists:users,username',
            'file_path' => 'required|mimes:pdf,docx,zip',
            'score' => 'nullable|numeric|min:0|max:10',
            'feedback' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Quy tắc xác thực khi cập nhật bài nộp.
     *
     * @return array
     */
    public function rulesForUpdate(): array
    {
        try {
            $assignmentSlug = $this->route('assignmentSlug');
            $studentUsername = $this->route('student_username');

            $assignment = Assignment::where('slug', $assignmentSlug)->first();

            if (!$assignment) {
                throw new Exception('Bài tập không tồn tại');
            }

            // Lấy ID sinh viên từ username
            $student = User::where('username', $studentUsername)->first();

            if (!$student) {
                throw new Exception('Sinh viên không tồn tại');
            }

            $submittedAssignment = SubmittedAssignment::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->first();

            if (!$submittedAssignment) {
                throw new Exception('Không tìm thấy bài nộp của sinh viên này cho bài tập này');
            }

            return [
                'score' => 'nullable|numeric|min:0|max:10',
                'feedback' => 'nullable|string|max:1000',
                'file_path' => 'nullable|mimes:pdf,docx,zip',
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Thông báo lỗi xác thực.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'file_path.required' => 'File nộp bài không thể để trống',
            'file_path.mimes' => 'File nộp bài phải có định dạng pdf, docx, hoặc zip',
            'score.numeric' => 'Điểm phải là một số hợp lệ',
            'score.min' => 'Điểm không thể nhỏ hơn 0',
            'score.max' => 'Điểm không thể lớn hơn 10',
            'feedback.string' => 'Phản hồi phải là một chuỗi văn bản',
            'feedback.max' => 'Phản hồi không được quá 1000 ký tự',
        ];
    }
}
