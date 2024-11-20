<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'username'=>'required',
            'content' => 'required|max:255'
        ];
    }

    public function rulesForUpdate()
    {
       
            return [
                'content' => 'required|max:255'
            ];
    }

    public function messages()
    {
        return [
            'content.required' => 'Nội dung comments không được trống',
            'content.max'=> 'Nội dung comments quá dài'
        ];
    }
}
