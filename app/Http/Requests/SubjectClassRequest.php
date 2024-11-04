<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectClassRequest extends FormRequest
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
            'class_slug' => 'required|exists:classes,slug',
            'subject_slugs' => 'required|array',
            'subject_slugs.*' => 'exists:subjects,slug'
        ];
    }

    public function rulesForUpdate()
    {

        return [
            'class_slug' => 'required|exists:classes,slug',
            'new_subject_slug'=>'required',
            'new_subject_slug.*' => 'exists:subjects,slug'
        ];
    }
    public function messages(): array
    {
        return [

            'class_slug.required' => 'Trường class_slug là bắt buộc.',
            'class_slug.exists' => 'Lớp không tồn tại.',
            'new_subject_slug.required' => 'Trường new_subject_slug là bắt buộc.',
            'new_subject_slug.*.exists' => 'Môn học với slug :input không tồn tại.',

        ];
    }
}
