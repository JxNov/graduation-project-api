<?php

namespace App\Services;

use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\DB;

class SubjectService
{

    public function store(array $data)
    {
        DB::beginTransaction();
        try {
            // Kiểm tra tên môn học có chứa số lớp tương ứng với block_level
            $gradeLevelFromName = $this->extractGradeLevel($data['name']);

            if ($gradeLevelFromName !== intval($data['block_level'])) {
                throw new Exception('Tên môn học và mã khối không khớp nhau.');
            }

            // Tạo môn học mới
            $subject = Subject::create($data);
            DB::commit();
            return $subject;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(array $data, $id)
    {
        DB::beginTransaction();
        try {
            $subject = Subject::findOrFail($id);

            // Kiểm tra tên môn học có chứa số lớp tương ứng với block_level
            $gradeLevelFromName = $this->extractGradeLevel($data['name']);

            if ($gradeLevelFromName !== intval($data['block_level'])) {
                throw new Exception('Tên môn học và mã khối không khớp nhau.');
            }

            $subject->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'block_level' => $data['block_level'],
            ]);

            DB::commit();
            return $subject;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $subject = Subject::findOrFail($id);
            $subject->delete();
            DB::commit();
            return null; // Trả về null để chỉ ra việc xóa thành công
        } catch (Exception $e) {
            DB::rollBack();
            throw $e; // Ném ra lỗi nếu có
        }
    }

    public function backup($id)
    {
        DB::beginTransaction();
        try {
            // Lấy môn học đã bị xóa
            $subject = Subject::withTrashed()->findOrFail($id);

            // Khôi phục môn học
            $subject->restore();

            DB::commit();

            return $subject; // Trả về môn học đã được khôi phục
        } catch (Exception $e) {
            DB::rollBack();
            throw $e; // Ném ra lỗi để controller xử lý
        }
    }
    // Phương thức để trích xuất số lớp từ tên môn học
    private function extractGradeLevel($name)
    {
        // Sử dụng biểu thức chính quy để tìm số ở cuối tên môn học (vd: Toán 6)
        if (preg_match('/\d+$/', $name, $matches)) {
            return intval($matches[0]);
        }

        // Nếu không tìm thấy số lớp, ném ra lỗi
        throw new Exception('Tên môn học không chứa số lớp.');
    }
}
