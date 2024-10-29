<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Hãy chọn danh sách học sinh',
            'file.file' => 'Tập tin phải là một file hợp lệ',
            'file.mimes' => 'Tập tin phải có định dạng .xlsx',
        ];
    }
}
