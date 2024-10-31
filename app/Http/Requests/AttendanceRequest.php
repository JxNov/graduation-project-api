<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'date' => 'nullable|date',
            'shifts' => 'nullable',
            'class_slug' => 'required',
            'students' => 'required',
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'date' => 'required|date',
            'shifts' => 'nullable',
            'class_slug' => 'required',
            'students' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'date.date' => 'Ngày phải là định dạng Năm/Tháng/Ngày',
            'class_slug' => 'Lớp học là bắt buộc'
        ];
    }
}
