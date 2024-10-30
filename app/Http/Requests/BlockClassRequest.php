<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockClassRequest extends FormRequest
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
            'block_slug' => 'required',
            'class_slug' => 'required',
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'block_slug' => 'required',
            'class_slug' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'block_slug.required' => 'Khối đang trống',
            'class_slug.required' => 'Lớp đang trống',
        ];
    }
}
