<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * UploadController — handle upload gambar untuk admin.
 *
 * Endpoint:
 *   POST /api/admin/upload          (multipart/form-data)
 *     - file:   file (required, image)
 *     - folder: 'jasa' | 'portofolio' | 'hero' (required)
 *
 *   DELETE /api/admin/upload        (json)
 *     - path: 'uploads/jasa/xxx.jpg' (required)
 *
 * File disimpan di storage/app/public/uploads/{folder}/{filename}
 * Akses publik via /storage/uploads/{folder}/{filename}
 *
 * Catatan setup (sekali saja):
 *   php artisan storage:link
 */
class UploadController extends Controller
{
    /** Folder yang diizinkan — mencegah path traversal */
    private const ALLOWED_FOLDERS = ['jasa', 'portofolio', 'hero'];

    /** Max file size dalam KB (5 MB) */
    private const MAX_SIZE_KB = 5120;

    /** MIME types yang diizinkan */
    private const ALLOWED_MIMES = 'jpeg,png,jpg,webp';

    /**
     * Upload gambar baru.
     * Return: { status, message, data: { path, url } }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'   => 'required|file|image|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_SIZE_KB,
            'folder' => 'required|string|in:' . implode(',', self::ALLOWED_FOLDERS),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $folder = $request->input('folder');
        $file   = $request->file('file');

        // Generate nama unik agar tidak tertimpa
        // Format: {timestamp}_{random}.{ext}
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_' . Str::random(12) . '.' . $ext;

        // Simpan ke disk 'public' (= storage/app/public/uploads/{folder})
        $path = $file->storeAs("uploads/{$folder}", $filename, 'public');

        if (!$path) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan file ke storage. Cek permission folder storage/app/public.',
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Gambar berhasil diupload',
            'data'    => [
                'path' => $path,                              // simpan ini ke DB
                'url'  => asset('storage/' . $path),          // untuk preview/tampilan
            ],
        ], 201);
    }

    /**
     * Hapus gambar dari storage.
     * Dipanggil saat user ganti gambar atau delete entity.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = $request->input('path');

        // Security: pastikan path dalam folder uploads (cegah path traversal)
        if (!Str::startsWith($path, 'uploads/')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Path tidak valid',
            ], 400);
        }

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'status'  => 'success',
                'message' => 'File tidak ditemukan (mungkin sudah dihapus)',
            ]);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'status'  => 'success',
            'message' => 'File dihapus',
        ]);
    }

    /**
     * Helper static: hapus file dengan aman, tanpa exception.
     * Dipakai oleh controller lain (Jasa, Portofolio) saat ganti gambar.
     */
    public static function deleteFile(?string $path): void
    {
        if (!$path) return;
        if (!Str::startsWith($path, 'uploads/')) return;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
