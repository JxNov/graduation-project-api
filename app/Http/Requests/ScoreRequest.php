<?php

namespace App\Http\Requests;

use App\Models\Score;
use Exception;
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
            'student_name' => 'required|exists:users,username',
            'subject_slug' => 'required|exists:subjects,slug',
            'class_slug' => 'required|exists:classes,slug',
            'semester_slug' => 'required|exists:semesters,slug',
            'detailed_scores.diem_mieng.he_so' => 'required|numeric|min:1|max:3',
            'detailed_scores.diem_mieng.score' => 'nullable|array',
            'detailed_scores.diem_15_phut.he_so' => 'required|numeric|min:1|max:3',
            'detailed_scores.diem_15_phut.score' => 'nullable|array',
            'detailed_scores.diem_mot_tiet.he_so' => 'required|numeric|min:1|max:3',
            'detailed_scores.diem_mot_tiet.score' => 'nullable|array',
            'detailed_scores.diem_giua_ki.he_so' => 'required|numeric|min:1|max:3',
            'detailed_scores.diem_giua_ki.score' => 'nullable|array',
            'detailed_scores.diem_cuoi_ki.he_so' => 'required|numeric|min:1|max:3',
            'detailed_scores.diem_cuoi_ki.score' => 'nullable|array',
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $score = Score::findOrFail($this->route('id'));

            return [
                'detailed_scores.diem_mieng.he_so' => 'required|numeric|min:1|max:3',
                'detailed_scores.diem_mieng.score' => 'nullable|array',
                'detailed_scores.diem_15_phut.he_so' => 'required|numeric|min:1|max:3',
                'detailed_scores.diem_15_phut.score' => 'nullable|array',
                'detailed_scores.diem_mot_tiet.he_so' => 'required|numeric|min:1|max:3',
                'detailed_scores.diem_mot_tiet.score' => 'nullable|array',
                'detailed_scores.diem_giua_ki.he_so' => 'required|numeric|min:1|max:3',
                'detailed_scores.diem_giua_ki.score' => 'nullable|array',
                'detailed_scores.diem_cuoi_ki.he_so' => 'required|numeric|min:1|max:3',
                'detailed_scores.diem_cuoi_ki.score' => 'nullable|array',
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
            'student_name.required' => 'Tên sinh viên là bắt buộc.',
            'student_name.exists' => 'Sinh viên không tồn tại.',
            'class_slug.required' => 'Lớp học là bắt buộc',
            'class_slug.exists' => 'Lớp học không tồn tại',
            'subject_slug.required' => 'Tên môn học là bắt buộc.',
            'subject_slug.exists' => 'Môn học không tồn tại.',
            'semester_slug.required' => 'Tên học kỳ là bắt buộc.',
            'semester_slug.exists' => 'Học kỳ không tồn tại.',
            'detailed_scores.required' => 'Chi tiết điểm là bắt buộc.',
            'detailed_scores.array' => 'Chi tiết điểm phải là một mảng.',
            'detailed_scores.*.numeric' => 'Mỗi điểm chi tiết phải là số.',
            'detailed_scores.*.min' => 'Mỗi điểm chi tiết không được nhỏ hơn :min.',
            'detailed_scores.*.max' => 'Mỗi điểm chi tiết không được lớn hơn :max.',
        ];
    }
}
