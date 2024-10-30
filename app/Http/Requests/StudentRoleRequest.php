<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentRoleRequest extends FormRequest
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
            'username' => ['required', 'exists:users,username','numeric'], 
            'slugRole' => ['required', 'exists:roles,slug','numeric'], 
        ];
    }

    public function rulesForUpdate(): array
    {


        return [
            'username' => ['required', 'exists:users,username','numeric'], 
            'slugRole' => ['required', 'exists:roles,slug','numeric'], 
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Trường username là bắt buộc.',
            'username.exists' => 'Người dùng không tồn tại.',
            'username.numeric'=>'Chỉ được là số nguyên',

            'slugRole.required' => 'Trường slugRole là bắt buộc.',
            'slugRole.exists' => 'Vai trò không tồn tại.',
            'slugRole.numeric'=>'Chỉ được là số nguyên',
        ];
    }
}
