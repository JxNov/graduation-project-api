<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
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
            'message' => 'required'
        ];
    }

    public function rulesForUpdate(): array
    {
        return [
            'message' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Hãy nhập nội dung muốn gửi'
        ];
    }
}
