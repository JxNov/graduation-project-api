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
            'block_id' => 'required|exists:blocks,id',
            'class_id' => 'required|exists:classes,id',
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'block_id' => 'required|exists:blocks,id',
            'class_id' => 'required|exists:classes,id',
        ];
    }

    public function messages()
    {
        return [
            'block_id.required' => 'Khối đang trống',
            'block_id.exists' => 'Khối không tồn tại hoặc đã bị xóa',
            'class_id.required' => 'Lớp đang trống',
            'class_id.exists' => 'Lớp không tồn tại hoặc đã bị xóa',
        ];
    }
}
