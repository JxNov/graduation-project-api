<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcademicYearClassRequest extends FormRequest
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id'
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Năm học không được bỏ trống',
            'academic_year_id.exists' => 'Năm học không tồn tại',
            'class_id.required' => 'Lớp không được bỏ trống',
            'class_id.exists' => 'Lớp không tồn tại'
        ];
    }
}
