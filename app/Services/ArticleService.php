<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Classes;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;


class ArticleService
{
    public function createPost(array $data)
{
    // Kiểm tra người dùng hiện tại có phải là giáo viên
    $teacher = User::whereHas('roles', function ($role) {
        $role->where('slug', 'like', 'teacher');
    })
        ->where('username', $data['username'])
        ->first();

    if ($teacher === null) {
        throw new Exception('Người này không phải giáo viên');
    }

    // Kiểm tra lớp có tồn tại không
    $class = Classes::where('slug', $data['class_slug'])->first();
    if ($class === null) {
        throw new Exception('Không tìm thấy lớp');
    }
    // Kiểm tra nếu có tệp đính kèm
    if (isset($data['attachments'])) {
        $firebase = app('firebase.storage');
        $storage = $firebase->getBucket();

        // Tạo đường dẫn Firebase
        $firebasePath = 'attachments/' . time() . '_' . $data['attachments']->getClientOriginalName();

        // Upload tệp lên Firebase Storage
        $storage->upload(
            file_get_contents($data['attachments']->getRealPath()),
            [
                'name' => $firebasePath
            ]
        );

        // Lưu đường dẫn Firebase vào cơ sở dữ liệu
        $data['attachments'] = $firebasePath;
    }

    // Tạo bài viết trong cơ sở dữ liệu
    $post = Article::create([
        'title' => $data['title'],
        'content' => $data['content'],
        'teacher_id' => $teacher->id, // Giáo viên đang đăng nhập
        'class_id' => $class->id, // Lớp mà bài viết thuộc về
        'attachments' => $data['attachments'] ?? null, // Đường dẫn tệp trên Firebase
    ]);

    return $post;
}



}