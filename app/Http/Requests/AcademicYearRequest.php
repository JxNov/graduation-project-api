<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class AcademicYearRequest extends FormRequest
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

    public function rulesForCreate()
    {
        return [
            'name' => 'required|max:50|unique:academic_years,name',
            'slug' => 'max:70|unique:academic_years,slug',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'generation_slug' => 'required'
        ];
    }

    public function rulesForUpdate()
    {
        try {
            $slug = $this->route('slug');
            $academicYear = AcademicYear::where('slug', $slug)->select('id', 'slug')->first();

            if (!$academicYear) {
                throw new Exception('Không tìm thấy năm học');
            }

            return [
                'name' => 'required|max:50|unique:academic_years,name,' . $academicYear->id,
                'slug' => 'max:70|unique:academic_years,slug,' . $academicYear->id,
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'generation_slug' => 'required'
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
            'name.required' => 'Tên năm học là bắt buộc.',
            'name.max' => 'Tên năm học không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên năm học đã tồn tại.',
            'slug.max' => 'Slug không được vượt quá 70 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu phải là một ngày hợp lệ.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc phải là một ngày hợp lệ.',
            'generation_slug.required' => 'ID thế hệ là bắt buộc.',
        ];
    }

}
