<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScoreRequest extends FormRequest
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
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->rulesForUpdate();
        }

        return [];
    }

    public function rulesForCreate(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'detailed_scores' => 'required|array',
            'detailed_scores.*.type' => 'required|string|max:50',
            'detailed_scores.*.score' => 'required|numeric|min:0|max:10',
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $score = Score::findOrFail($this->route('id'));

            return [
                'student_id' => 'required|exists:users,id',
                'subject_id' => 'required|exists:subjects,id',
                'semester_id' => 'required|exists:semesters,id',
                'detailed_scores' => 'required|array',
                'detailed_scores.*.type' => 'required|string|max:50',
                'detailed_scores.*.score' => 'required|numeric|min:0|max:10',
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
            'student_id.required' => 'Mã sinh viên là bắt buộc.',
            'student_id.exists' => 'Sinh viên không tồn tại.',
            'subject_id.required' => 'Mã môn học là bắt buộc.',
            'subject_id.exists' => 'Môn học không tồn tại.',
            'semester_id.required' => 'Mã học kỳ là bắt buộc.',
            'semester_id.exists' => 'Học kỳ không tồn tại.',
            'detailed_scores.required' => 'Chi tiết điểm là bắt buộc.',
            'detailed_scores.array' => 'Chi tiết điểm phải là một mảng.',
            'detailed_scores.*.numeric' => 'Mỗi điểm chi tiết phải là số.',
            'detailed_scores.*.min' => 'Mỗi điểm chi tiết không được nhỏ hơn :min.',
            'detailed_scores.*.max' => 'Mỗi điểm chi tiết không được lớn hơn :max.',
        ];
    }
}

