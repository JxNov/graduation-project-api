<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'article_id' => 'required|exists:articles,id',
            'content' => 'required|max:255'
        ];
    }

    public function rulesForUpdate()
    {
        return [
            'article_id' => 'nullable|exists:articles,id',
            'content' => 'required|max:255'
        ];
    }

    public function messages()
    {
        return [
            'article_id.required' => 'Không có bài viết được tìm thấy',
            'article_id.exists' => 'Bài viết không tồn tại',
            'content.required' => 'Nội dung bình luận không được trống',
            'content.max' => 'Nội dung bình luận quá dài'
        ];
    }
}
