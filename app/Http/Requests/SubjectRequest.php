<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
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
            'name' => ['required', 'max:50', 'unique:subjects'],
            'description' => ['required', 'max:500', 'string', 'min:10'],
            'block_level' => ['required', 'integer', 'between:6,9']
        ];
    }

    public function rulesForUpdate(): array
    {

        return [
            'name' => ['required', 'max:50', 'unique:subjects'],
            'description' => ['required', 'max:500', 'string', 'min:10'],
            'block_level' => ['required', 'numeric', 'between:6,9']
        ];
    }

    public function messages(): array
    {
        return [

            'name.required' => 'Tên môn học là bắt buộc.',
            'name.max' => 'Tên môn học không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên môn học đã tồn tại, vui lòng chọn tên khác.',

            'description.required' => 'Mô tả là bắt buộc.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.min' => 'Mô tả phải có ít nhất 10 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',

            'block_level.required' => 'Khối là bắt buộc.',
            'block_level.numeric' => 'Khối phải là số nguyên.',
            'block_level.between' => 'Khối chỉ được nhập số từ 6 đến 9.'

        ];
    }
}
