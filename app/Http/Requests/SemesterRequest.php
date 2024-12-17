<?php

namespace App\Http\Requests;

use App\Models\Semester;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class SemesterRequest extends FormRequest
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
            'name' => 'required|max:50',
            'slug' => 'max:70|unique:semesters,slug',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'academic_year_slug' => 'required'
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $slug = $this->route('slug');
            $semester = Semester::where('slug', $slug)->select('id', 'slug')->first();

            if (!$semester) {
                throw new Exception('Không tìm thấy kỳ học');
            }

            return [
                'name' => 'required|max:50',
                'slug' => 'max:70|unique:semesters,slug,' . $semester->id,
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'academic_year_slug' => 'required'
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
            'name.required' => 'Tên kỳ học là bắt buộc.',
            'name.max' => 'Tên kỳ học không được vượt quá :max ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'academic_year_slug.required' => 'Năm học là bắt buộc.',
        ];
    }

}
