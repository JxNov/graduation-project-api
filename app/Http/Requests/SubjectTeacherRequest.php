<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectTeacherRequest extends FormRequest
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
    public function rulesForCreate(): array
    {
        return [
            'username' => 'required|exists:users,username',
            'subjectSlug' => 'required|exists:subjects,slug',
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'username' => 'required|exists:users,username',
        ];
    }
    public function messages()
    {
        return [
            'username.required' => 'Username đang trống',
            'username.exists' => 'Username không tồn tại hoặc đã bị xóa',
            'subjectSlug.required' => 'Môn học đang trống',
            'subjectSlug.exists' => 'Môn học không tồn tại hoặc đã bị xoá.',
        ];
    }
}
