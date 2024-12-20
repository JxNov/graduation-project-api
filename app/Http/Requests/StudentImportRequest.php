<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx',
            'generationSlug' => 'required|exists:generations,slug',
            'academicYearSlug' => 'required|exists:academic_years,slug',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Hãy chọn danh sách học sinh',
            'file.file' => 'Tập tin phải là một file hợp lệ',
            'file.mimes' => 'Tập tin phải có định dạng .xlsx',
            'generationSlug.required' => 'Khóa học sinh đang trống',
            'generationSlug.exists' => 'Khóa học sinh không tồn tại hoặc đã bị xóa',
            'academicYearSlug.required' => 'Năm học đang trống',
            'academicYearSlug.exists' => 'Năm học không tồn tại hoặc đã bị xóa',
        ];
    }
}
