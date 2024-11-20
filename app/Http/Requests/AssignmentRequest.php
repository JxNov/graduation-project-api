<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return $this->rulesForCreate();
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->rulesForUpdate();
        }

        return [];
    }

    public function rulesForCreate(): array
    {
        return [
            'title' => 'required|max:100|unique:assignments,title',
            'description' => 'nullable',
            'due_date' => 'required|date',
            'criteria' => 'required',
            'subject_slug' => 'required|exists:subjects,slug',
            'username' => 'required|exists:users,username',
            'class_slug' => 'required|exists:classes,slug',
            'semester_slug' => 'required|exists:semesters,slug',
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $slug = $this->route('assignmentSlug'); // Sử dụng slug từ route
            $assignment = Assignment::where('slug', $slug)->first();

            if (!$assignment) {
                throw new Exception('Không tìm thấy bài tập');
            }

            return [
                'title' => 'required|max:100|unique:assignments,title,' . $assignment->id,
                'description' => 'nullable',
                'due_date' => 'required|date',
                'criteria' => 'required',
                'subject_slug' => 'required|exists:subjects,slug',
                'username' => 'required|exists:users,username',
                'class_slug' => 'required|exists:classes,slug',
                'semester_slug' => 'required|exists:semesters,slug',
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài tập đang trống',
            'title.max' => 'Tiêu đề quá dài, tối đa 100 ký tự',
            'title.unique' => 'Tên bài tập đã tồn tại',
            'due_date.required' => 'Ngày hết hạn đang trống',
            'due_date.date' => 'Ngày hết hạn phải là ngày hợp lệ',
            'criteria.required' => 'Tiêu chí bài tập đang trống',
            'subject_slug.required' => 'Môn học chưa được chọn',
            'subject_slug.exists' => 'Môn học không hợp lệ',
            'username.required' => 'Giáo viên chưa được chọn',
            'username.exists' => 'Giáo viên không hợp lệ',
            'class_slug.required' => 'Hãy chọn 1 lớp cho bài tập',
            'class_slug.exists' => 'Lớp học không hợp lệ',
            'semester_slug.required' => 'Hãy chọn 1 học kỳ cho bài tập',
            'semester_slug.exists' => 'Học kỳ không hợp lệ',
        ];
    }
}
