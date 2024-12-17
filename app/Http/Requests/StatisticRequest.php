<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatisticRequest extends FormRequest
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
        return [
            "subject_slug" => "required|exists:subjects,slug",
            "class_slug" => "required|exists:classes,slug",
            "semester_slug" => "required|exists: semesters,slug"
        ];
    }

    public function messages()
    {
        return [
            "subject_slug.required" => "Môn học là bắt buộc",
            "subject_slug.exists" => "Môn học không tồn tại",
            "class_slug.required" => "Lớp học là bắt buộc",
            "class_slug.exists" => "Lớp học không tồn tại",
            "semester_slug.required" => "Kì học là bắt buộc",
            "semester_slug.exists" => "Kì học không tồn tại",
        ];
    }
}
