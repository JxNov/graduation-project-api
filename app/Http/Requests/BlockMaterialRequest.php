<?php

namespace App\Http\Requests;

use App\Models\Material;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class BlockMaterialRequest extends FormRequest
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
            'title' => 'required|max:100|unique:materials,title',
            'slug' => 'max:130',
            'description' => 'nullable',
            'file_path' => 'required|mimes:docx',
            'subject_slug' => 'required',
            'block_slug' => 'required',
        ];
    }

    public function rulesForUpdate(): array
    {
        try {
            $slug = $this->route('slug');
            $material = Material::where('slug', $slug)->select('id', 'slug')->first();

            if (!$material) {
                throw new Exception('Không tìm thấy tài liệu');
            }

            return [
                'title' => 'required|max:100|unique:materials,title,' . $material->id,
                'slug' => 'max:130',
                'description' => 'nullable',
                'file_path' => 'nullable|mimes:docx',
                'subject_slug' => 'required',
                'block_slug' => 'required',
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
            'title.required' => 'Tiêu đề tài liệu đang trống',
            'title.max' => 'Tiêu đề quá dài, tối đa 100 ký tự',
            'title.unique' => 'Tên tài liệu đã tồn tại',
            'file_path.required' => 'Tài liệu đang trống',
            'file_path.mimes' => 'Tài liệu phải có định dạng .docx',
            'subject_slug.required' => 'Môn học chưa được chọn',
            'block_slug.required' => 'Hãy chọn 1 khối cho tài liệu',
        ];
    }
}
