<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassMaterialRequest extends FormRequest
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
            'material_slug' => 'required',
            'class_slug' => 'required'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'material_slug' => 'required',
            'class_slug' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'material_slug.required' => 'Tài liệu không được bỏ trống',
            'class_slug.required' => 'Lớp không được bỏ trống',
        ];
    }
}
