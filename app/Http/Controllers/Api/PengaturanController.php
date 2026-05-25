<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengaturanController extends Controller
{
    /**
     * Daftar kunci pengaturan yang nilainya berisi PATH file gambar.
     * Untuk kunci-kunci ini, response akan otomatis menyertakan
     * versi `_url` yang sudah jadi URL lengkap (siap dipakai di <img src>).
     */
    private const IMAGE_KEYS = ['hero_image', 'about_image'];

    /**
     * List semua pengaturan (publik).
     * Output:
     *   data: { kunci: nilai, ..., hero_image_url: 'http://...' }   ← url disuntik untuk image keys
     *   detail: [...]
     */
    public function index(Request $request)
    {
        $query = Pengaturan::query();
        if ($grup = $request->query('grup')) {
            $query->where('grup', $grup);
        }

        $items = $query->get();

        // Format jadi key-value
        $kv = $items->mapWithKeys(fn ($p) => [$p->kunci => $p->nilai])->toArray();

        // Suntik URL siap pakai untuk semua kunci gambar yang ada
        foreach (self::IMAGE_KEYS as $imgKey) {
            if (!empty($kv[$imgKey])) {
                $kv[$imgKey . '_url'] = asset('storage/' . $kv[$imgKey]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $kv,
            'detail' => $items->map(fn ($p) => [
                'kunci' => $p->kunci,
                'nilai' => $p->nilai,
                'grup'  => $p->grup,
                'tipe'  => $p->tipe,
            ]),
        ]);
    }

    /**
     * ADMIN: Update batch.
     * Bila ada kunci gambar (mis. hero_image) yang nilainya berubah,
     * file lama otomatis dihapus dari storage.
     */
    public function updateBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings'         => 'required|array|min:1',
            'settings.*.kunci' => 'required|string|max:100',
            'settings.*.nilai' => 'nullable|string',
            'settings.*.grup'  => 'nullable|string|max:50',
            'settings.*.tipe'  => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->settings as $s) {
            $kunci = $s['kunci'];
            $nilai = $s['nilai'] ?? '';

            // Untuk kunci gambar: hapus file lama jika diganti
            if (in_array($kunci, self::IMAGE_KEYS, true)) {
                $old = Pengaturan::where('kunci', $kunci)->value('nilai');
                if ($old && $old !== $nilai) {
                    UploadController::deleteFile($old);
                }
            }

            Pengaturan::set(
                $kunci,
                $nilai,
                $s['grup'] ?? 'umum',
                $s['tipe'] ?? 'text'
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengaturan diperbarui',
        ]);
    }
}