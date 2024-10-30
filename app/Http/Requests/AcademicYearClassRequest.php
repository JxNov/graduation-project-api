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
            'academic_year_slug' => 'required',
            'class_slug' => 'required'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'academic_year_slug' => 'required',
            'class_slug' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_slug.required' => 'Năm học không được bỏ trống',
            'class_slug.required' => 'Lớp không được bỏ trống',
        ];
    }
}
