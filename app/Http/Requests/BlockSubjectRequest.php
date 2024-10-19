<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockSubjectRequest extends FormRequest
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
            'block_id' => 'required|exists:blocks,id',
            'subject_id' => 'required|exists:subjects,id',
        ];
    }

    public function rulesForUpdate(): array
    {


        return [
            'block_id' => 'required|exists:blocks,id',
            'subject_id' => 'required|exists:subjects,id',
        ];
    }

    public function messages(): array
    {
        return [
            'block_id.required' => 'ID Khối là bắt buộc.',
            'subject_id.required' => 'ID Môn học là bắt buộc',
            'block_id.exists' => 'ID Khối không tồn tại',
            'subject_id.exists' => "ID Môn học không tồn tại"
        ];
    }
}
