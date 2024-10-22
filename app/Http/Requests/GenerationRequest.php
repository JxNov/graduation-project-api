<?php

namespace App\Http\Requests;

use App\Models\Generation;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class GenerationRequest extends FormRequest
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

    public function rulesForCreate()
    {
        return [
            'name' => 'required|max:50|unique:generations,name',
            'slug' => 'max:70|unique:generations,slug',
            'year' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
    }

    public function rulesForUpdate()
    {
        try {
            $slug = $this->route(param: 'slug');
            $generation = Generation::where('slug', $slug)->select('id', 'slug')->first();

            if (!$generation) {
                throw new Exception('Không tìm thấy khóa học');
            }
            return [
                'name' => 'required|max:50|unique:generations,name,' . $generation->id,
                'slug' => 'max:70|unique:generations,slug,' . $generation->id,
                'year' => 'required|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên là bắt buộc.',
            'name.max' => 'Tên không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên đã tồn tại.',
            'slug.max' => 'Slug không được vượt quá 70 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'year.required' => 'Năm là bắt buộc.',
            'year.numeric' => 'Năm phải là một số.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
        ];
    }
}
