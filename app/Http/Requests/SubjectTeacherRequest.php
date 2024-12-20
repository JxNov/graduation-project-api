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
            'subjectSlugs' => 'required|exists:subjects,slug',
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'subjectSlugs' => 'required|exists:subjects,slug',
        ];
    }
    public function messages()
    {
        return [
            'subjectSlugs.required' => 'Môn học đang trống',
            'subjectSlugs.exists' => 'Môn học không tồn tại hoặc đã bị xoá.',
        ];
    }
}
