<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
  public function upload(Request $request)
  {
    $request->validate([
      'file' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
    ]);

    $file   = $request->file('file');
    $folder = 'uploads/' . date('Y') . '/' . date('m');

    // tên + đuôi an toàn
    $originalName = $file->getClientOriginalName() ?: 'file';
    $nameOnly     = pathinfo($originalName, PATHINFO_FILENAME) ?: 'file';
    $extension    = strtolower($file->getClientOriginalExtension() ?: '');

    if ($extension === '') {
      $map = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
      $extension = $map[$file->getMimeType()] ?? 'jpg';
    }

    $baseSlug = Str::slug($nameOnly, '-', 'vi') ?: 'file';

    // Chuẩn hóa đường dẫn (tránh rỗng / ký tự lạ)
    $folder   = trim($folder, " \t\n\r\0\x0B/");         // uploads/2025/08
    $baseSlug = trim($baseSlug, " .\t\n\r\0\x0B");       // không rỗng
    $extension = trim($extension, " .\t\n\r\0\x0B") ?: 'jpg';

    // Tạo thư mục trước (dù Laravel thường tự tạo)
    Storage::disk('public')->makeDirectory($folder);

    // Lấy tên không trùng
    $filename = $this->uniqueFilename($folder, $baseSlug, $extension);

    $path = storage_path('app/public/' . $folder);
    $file->move($path, $filename);
    $url = Storage::url($folder . "/" . $filename);
    return response()->json([
      'success'  => true,
      'filename' => $filename,
      'url'     => asset($url),
    ]);
  }


  /**
   * Tạo tên file không trùng trong disk 'public'
   */
  private function uniqueFilename(string $folder, string $baseSlug, string $extension): string
  {
    $disk = Storage::disk('public');

    // thử tên gốc trước
    $candidate = "{$baseSlug}.{$extension}";
    if (!$disk->exists("{$folder}/{$candidate}")) {
      return $candidate;
    }

    // nếu đã tồn tại, tăng hậu tố -1, -2, -3, ...
    $i = 1;
    do {
      $candidate = "{$baseSlug}-{$i}.{$extension}";
      $i++;
    } while ($disk->exists("{$folder}/{$candidate}"));

    return $candidate;
  }
}
