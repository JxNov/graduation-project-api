<?php

namespace App\Http\Requests;

use App\Models\Classes;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
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
            'name' => 'required|max:50',
            'slug' => 'max:70|unique:classes,slug',
            'username' => 'required',
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $slug = $this->route('slug');
            $class = Classes::where('slug', $slug)->select('id', 'slug')->first();

            if (!$class) {
                throw new Exception('Không tìm thấy năm học');
            }

            return [
                'name' => 'required|max:50',
                'slug' => 'max:70|unique:classes,slug,' . $class->id,
                'username' => 'required',
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên lớp là bắt buộc.',
            'name.max' => 'Tên lớp không được vượt quá 50 ký tự.',
            'slug.max' => 'Slug không được vượt quá 70 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'username.required' => 'Giáo viên là bắt buộc.',
        ];
    }

}
