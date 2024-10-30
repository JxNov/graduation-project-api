<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
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
            'title' => 'required|max:100',
            'slug' => 'max:130',
            'description' => 'nullable',
            'file_path' => 'required|mimes:docx',
            'subject_slug' => 'required',
            'username' => 'required'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'title' => 'required|max:100',
            'slug' => 'max:130',
            'description' => 'nullable',
            'file_path' => 'nullable|mimes:docx',
            'subject_slug' => 'required',
            'username' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề tài liệu đang trống',
            'title.max' => 'Tiêu đề quá dài, tối đa 100 ký tự',
            'file_path.required' => 'Tài liệu đang trống',
            'file_path.mimes' => 'Tài liệu phải có định dạng .docx',
            'subject_slug.required' => 'Môn học chưa được chọn',
            'username.required' => 'Giáo viên chưa được chọn',
        ];
    }
}
