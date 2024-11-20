<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'title' => 'required|max:255',
            'content' => 'required',
            'username' => 'required|string|exists:users,username',
            'class_slug' => 'required|string|exists:classes,slug',
            'attachments' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,docx',
        ];
    }

    public function rulesForUpdate()
    {

        return [
            'title' => 'required|max:255',
            'content' => 'required',
            'username' => 'required|string|exists:users,username',
            'class_slug' => 'required|string|exists:classes,slug',
            'attachments' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,docx',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Tiêu đề bài viết không được để trống.',
            'title.max' => 'Tiêu đề bài viết không được vượt quá 255 ký tự.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'attachments.file' => 'Tệp đính kèm phải là một tệp hợp lệ.',
            'attachments.max' => 'Tệp đính kèm không được vượt quá 10MB.',
            'attachments.mimes' => 'Tệp đính kèm phải có định dạng jpg, jpeg, png, pdf hoặc docx.',
        ];
    }
}
