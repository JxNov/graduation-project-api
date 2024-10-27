<?php

namespace App\Http\Requests;

use App\Models\Block;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class BlockRequest extends FormRequest
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
            'name' => 'required|max:50|unique:blocks,name',
            'slug' => 'max:70',
            'level' => 'required|integer|min:6|max:9'
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $slug = $this->route('slug');
            $block = Block::where('slug', $slug)->select('id', 'slug')->first();

            if (!$block) {
                throw new Exception('Không tìm thấy năm học');
            }

            return [
                'name' => 'required|max:50|unique:blocks,name,' . $block->id,
                'slug' => 'max:70',
                'level' => 'required|integer|min:6|max:9'
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
            'name.required' => 'Tên là bắt buộc',
            'name.max' => 'Tên không được vượt quá 50 ký tự',
            'name.unique' => 'Tên đã tồn tại',
            'slug.max' => 'Slug không được vượt quá 70 ký tự',
            'level.required' => 'Cấp độ là bắt buộc',
            'level.integer' => 'Cấp độ phải là 1 số',
            'level.min' => 'Cấp độ tối thiểu là 6',
            'level.max' => 'Cấp độ tối đa là 9',
        ];
    }

}
