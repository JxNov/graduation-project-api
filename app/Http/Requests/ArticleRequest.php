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
        ];
    }

    public function rulesForUpdate()
    {

        return [
            'content' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'content.required' => 'Nội dung bài viết không được để trống',
        ];
    }
}
