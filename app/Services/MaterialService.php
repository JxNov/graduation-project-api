<?php
namespace App\Services;

use App\Models\Block;
use App\Models\Classes;
use App\Models\Material;
use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialService
{
    public function token()
    {
        $client_id = \Config('services.google.client_id');
        $client_secret = \Config('services.google.client_secret');
        $refresh_token = \Config('services.google.refresh_token');
        // $folder_id = \Config('services.google.folder_id');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        // dd($response->json());

        $accessToken = json_decode((string) $response->getBody(), true)['access_token'];
        // dd($accessToken);
        return $accessToken;
    }

    public function createNewMaterialForClass($data)
    {
        return DB::transaction(function () use ($data) {
            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $teacher = Auth::user();

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;

            $data['slug'] = Str::slug($subject->slug . '-' . $data['title']) . '-' . rand(1111, 9999);

            if (isset($data['file_path'])) {
                $fileName = $class->name . ' - ' . ucfirst($data['title']) . ' - ' . $teacher->name;
                $mimeType = $data['file_path']->getClientMimeType();

                $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'multipart/related; boundary="foo_bar_baz"',
                    ],
                    'body' => implode("\r\n", [
                        '--foo_bar_baz',
                        'Content-Type: application/json; charset=UTF-8',
                        '',
                        json_encode([
                            'name' => $fileName,
                            'parents' => [\Config('services.google.folder_id')],
                            'mimeType' => $mimeType,
                        ]),
                        '--foo_bar_baz',
                        'Content-Type: ' . $mimeType,
                        'Content-Transfer-Encoding: base64',
                        '',
                        base64_encode(file_get_contents($data['file_path']->getRealPath())),
                        '--foo_bar_baz--',
                    ]),
                ]);

                if ($response->getStatusCode() == 200) {
                    $file_id = json_decode($response->getBody()->getContents())->id;
                    $uploadedfile = new Material;
                    $uploadedfile->title = $data['title'];
                    $uploadedfile->slug = $data['slug'];
                    $uploadedfile->description = $data['description'];
                    $uploadedfile->file_path = $file_id;
                    $uploadedfile->subject_id = $subject->id;
                    $uploadedfile->teacher_id = $teacher->id;
                    $uploadedfile->save();

                    $uploadedfile->classes()->sync($class->id);
                    return $uploadedfile;
                } else {
                    throw new Exception('Tải file lên Google Drive không thành công');
                }
            }
        });
    }

    public function updateMaterialForClass($data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            $material = Material::where('slug', $slug)->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $class = Classes::where('slug', $data['class_slug'])->first();

            if ($class === null) {
                throw new Exception('Lớp không tồn tại hoặc đã bị xóa');
            }

            $teacher = Auth::user();

            if (isset($data['file_path'])) {
                if ($material->file_path) {
                    $client->request('DELETE', 'https://www.googleapis.com/drive/v3/files/' . $material->file_path, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                        ],
                    ]);
                }

                $fileName = $class->name . ' - ' . ucfirst($data['title']) . ' - ' . $teacher->name;
                $mimeType = $data['file_path']->getClientMimeType();

                $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'multipart/related; boundary="foo_bar_baz"',
                    ],
                    'body' => implode("\r\n", [
                        '--foo_bar_baz',
                        'Content-Type: application/json; charset=UTF-8',
                        '',
                        json_encode([
                            'name' => $fileName,
                            'parents' => [\Config('services.google.folder_id')],
                            'mimeType' => $mimeType,
                        ]),
                        '--foo_bar_baz',
                        'Content-Type: ' . $mimeType,
                        'Content-Transfer-Encoding: base64',
                        '',
                        base64_encode(file_get_contents($data['file_path']->getRealPath())),
                        '--foo_bar_baz--',
                    ]),
                ]);

                if ($response->getStatusCode() == 200) {
                    $file_id = json_decode($response->getBody()->getContents())->id;
                    $data['file_path'] = $file_id;
                } else {
                    throw new Exception('Tải file mới lên Google Drive không thành công');
                }
            }

            $material->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'file_path' => $data['file_path'] ?? $material->file_path,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'slug' => $material->slug,
            ]);

            $material->classes()->sync($class->id);

            return $material;
        });
    }

    public function createNewMaterialForBlock($data)
    {
        return DB::transaction(function () use ($data) {
            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();

            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $teacher = Auth::user();

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;

            $data['slug'] = $subject->slug . '-' . $data['title'] . '-' . rand(1111, 9999);

            if (isset($data['file_path'])) {
                $fileName = $block->name . ' - ' . $subject->name . ' - ' . ucfirst($data['title']) . ' - ' . $teacher->name;
                $mimeType = $data['file_path']->getClientMimeType();

                $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'multipart/related; boundary="foo_bar_baz"',
                    ],
                    'body' => implode("\r\n", [
                        '--foo_bar_baz',
                        'Content-Type: application/json; charset=UTF-8',
                        '',
                        json_encode([
                            'name' => $fileName,
                            'parents' => [\Config('services.google.folder_id')],
                            'mimeType' => $mimeType,
                        ]),
                        '--foo_bar_baz',
                        'Content-Type: ' . $mimeType,
                        'Content-Transfer-Encoding: base64',
                        '',
                        base64_encode(file_get_contents($data['file_path']->getRealPath())),
                        '--foo_bar_baz--',
                    ]),
                ]);

                if ($response->getStatusCode() == 200) {
                    $file_id = json_decode($response->getBody()->getContents())->id;
                    $data['file_path'] = $file_id;
                } else {
                    throw new Exception('Tải file lên Google Drive không thành công');
                }
            }

            $material = Material::create($data);

            $material->blocks()->sync($block->id);

            return $material;
        });
    }

    public function updateMaterialForBlock($data, $slug)
    {
        return DB::transaction(function () use ($data, $slug) {
            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            $material = Material::where('slug', $slug)->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $subject = Subject::where('slug', $data['subject_slug'])->first();

            if ($subject === null) {
                throw new Exception('Môn học không tồn tại hoặc đã bị xóa');
            }

            $block = Block::where('slug', $data['block_slug'])->first();

            if ($block === null) {
                throw new Exception('Khối không tồn tại hoặc đã bị xóa');
            }

            $teacher = Auth::user();

            $data['subject_id'] = $subject->id;
            $data['teacher_id'] = $teacher->id;

            if (isset($data['file_path'])) {
                if ($material->file_path) {
                    $client->request('DELETE', 'https://www.googleapis.com/drive/v3/files/' . $material->file_path, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                        ],
                    ]);
                }

                $fileName = $block->name . ' - ' . $subject->name . ' - ' . ucfirst($data['title']) . ' - ' . $teacher->name;
                $mimeType = $data['file_path']->getClientMimeType();

                $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'multipart/related; boundary="foo_bar_baz"',
                    ],
                    'body' => implode("\r\n", [
                        '--foo_bar_baz',
                        'Content-Type: application/json; charset=UTF-8',
                        '',
                        json_encode([
                            'name' => $fileName,
                            'parents' => [\Config('services.google.folder_id')],
                            'mimeType' => $mimeType,
                        ]),
                        '--foo_bar_baz',
                        'Content-Type: ' . $mimeType,
                        'Content-Transfer-Encoding: base64',
                        '',
                        base64_encode(file_get_contents($data['file_path']->getRealPath())),
                        '--foo_bar_baz--',
                    ]),
                ]);

                if ($response->getStatusCode() == 200) {
                    $file_id = json_decode($response->getBody()->getContents())->id;
                    $data['file_path'] = $file_id;
                } else {
                    throw new Exception('Tải file mới lên Google Drive không thành công');
                }
            }

            $material->update($data);

            $material->blocks()->sync($block->id);

            return $material;
        });
    }

    public function downloadMaterial($slug)
    {
        try {
            $material = Material::where('slug', $slug)->first();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            $fileId = $material->file_path;

            if (!$fileId) {
                throw new Exception('File không tồn tại trong hệ thống');
            }

            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'stream' => true,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Không thể tải file từ Google Drive');
            }

            $fileName = $material->title . '.docx';

            return response()->streamDownload(function () use ($response) {
                echo $response->getBody()->getContents();
            }, $fileName);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function forceDeleteMaterial($slug)
    {
        return DB::transaction(function () use ($slug) {
            $material = Material::where('slug', $slug)->first();

            $accessToken = $this->token();
            $client = new \GuzzleHttp\Client();

            if ($material === null) {
                throw new Exception('Tài liệu không tồn tại hoặc đã bị xóa');
            }

            if ($material->file_path) {
                $client->request('DELETE', 'https://www.googleapis.com/drive/v3/files/' . $material->file_path, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]);
            }

            $material->forceDelete();
        });
    }
}