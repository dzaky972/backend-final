<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portofolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortofolioController extends Controller
{
    /**
     * List portofolio (publik bisa akses).
     * Query: ?featured=1 untuk hanya yang ditampilkan di beranda.
     */
    public function index(Request $request)
    {
        $query = Portofolio::query()->orderBy('urutan', 'asc')->orderBy('id_portofolio', 'desc');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $items = $query->get();

        return response()->json([
            'status' => 'success',
            'data'   => $items->map(fn ($p) => $this->format($p)),
        ]);
    }

    public function show($id)
    {
        $p = Portofolio::find($id);
        if (!$p) return response()->json(['status' => 'error', 'message' => 'Portofolio tidak ditemukan'], 404);

        return response()->json(['status' => 'success', 'data' => $this->format($p)]);
    }

    /**
     * ADMIN: Create.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(false));

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $portofolio = Portofolio::create($validator->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Portofolio berhasil dibuat',
            'data'    => $this->format($portofolio),
        ], 201);
    }

    /**
     * ADMIN: Update.
     * Hapus file gambar lama otomatis bila diganti.
     */
    public function update(Request $request, $id)
    {
        $p = Portofolio::find($id);
        if (!$p) return response()->json(['status' => 'error', 'message' => 'Portofolio tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Jika ada perubahan gambar → hapus file lama
        if (array_key_exists('gambar', $validated) && $validated['gambar'] !== $p->gambar) {
            UploadController::deleteFile($p->gambar);
        }

        $p->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Portofolio diperbarui',
            'data'    => $this->format($p->fresh()),
        ]);
    }

    /**
     * ADMIN: Delete (hapus juga file gambar dari storage).
     */
    public function destroy($id)
    {
        $p = Portofolio::find($id);
        if (!$p) return response()->json(['status' => 'error', 'message' => 'Portofolio tidak ditemukan'], 404);

        UploadController::deleteFile($p->gambar);

        $p->delete();
        return response()->json(['status' => 'success', 'message' => 'Portofolio dihapus']);
    }

    /* ─────────────────────────────────────────── */

    private function rules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        $opt = $partial ? 'sometimes|nullable' : 'nullable';
        return [
            'judul'          => "$req|string|max:255",
            'deskripsi'      => "$opt|string",
            'kategori'       => "$opt|string|max:100",
            'klien'          => "$opt|string|max:200",
            'tanggal_proyek' => "$opt|date",
            'icon'           => "$opt|string|max:10",
            'img_bg'         => "$opt|string|max:500",
            'gambar'         => "$opt|string|max:255", // ← BARU
            'tag'            => "$opt|string|max:100",
            'tag_color'      => "$opt|string|max:20",
            'is_featured'    => "$opt|boolean",
            'urutan'         => "$opt|integer",
        ];
    }

    private function format(Portofolio $p): array
    {
        return [
            'id'             => $p->id_portofolio,
            'id_portofolio'  => $p->id_portofolio,
            'judul'          => $p->judul,
            'label'          => $p->judul,
            'deskripsi'      => $p->deskripsi,
            'kategori'       => $p->kategori,
            'klien'          => $p->klien,
            'tanggal_proyek' => $p->tanggal_proyek?->format('Y-m-d'),
            'icon'           => $p->icon ?: '🎬',
            'img_bg'         => $p->img_bg,
            'imgBg'          => $p->img_bg,
            // ── Field gambar (BARU) ──────────────────────────────
            'gambar'         => $p->gambar,
            'gambar_url'     => $p->gambar ? asset('storage/' . $p->gambar) : null,
            // ─────────────────────────────────────────────────────
            'tag'            => $p->tag,
            'tag_color'      => $p->tag_color,
            'tagColor'       => $p->tag_color,
            'is_featured'    => $p->is_featured,
            'isFeatured'     => $p->is_featured,
            'urutan'         => $p->urutan,
        ];
    }
}