<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Exception;
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
        'block_slug' => ['required', 'exists:blocks,slug'],
        'subjects' => ['required', 'array', 'min:1'], 
        'subjects.*.name' => ['required', 'string', 'max:50', 'unique:subjects,name'], 
        'subjects.*.description' => ['nullable', 'string', 'max:500', 'min:10'], 
    ];
    
}


    public function rulesForUpdate(): array
    {
        $slug = $this->route('slug');
            $subject = Subject::where('slug', $slug)->select('id', 'slug')->first();

            if (!$subject) {
                throw new Exception('Không tìm thấy môn học');
            } 
            return [
                'block_slug' => ['required', 'exists:blocks,slug'],
                'subjects' => ['required', 'array', 'min:1'], 
                'subjects.*.name' => ['required', 'string', 'max:50', 'unique:subjects,name'], 
                'subjects.*.description' => ['nullable', 'string', 'max:500', 'min:10'],
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

            'class_slug.required' => 'Trường class_slug là bắt buộc.',
            'class_slug.exists' => 'Lớp không tồn tại.',
            'block_slug.required' => 'Trường block_slug là bắt buộc.',
            'block_slug.exists' => 'Lớp không tồn tại.',
        ];
    }
}
