<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
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
            'content' => 'required',
            'class_slug' => 'required|exists:classes,slug',
        ];
    }

    public function rulesForUpdate()
    {

        return [
            'content' => 'required',
            'class_slug' => 'required|exists:classes,slug',
        ];
    }

    public function messages()
    {
        return [
            'content.required' => 'Nội dung bài viết không được để trống',
            'class_slug.required' => 'Hãy chọn 1 lớp',
            'class_slug.exists' => 'Lớp học không tồn tại',
        ];
    }
}
