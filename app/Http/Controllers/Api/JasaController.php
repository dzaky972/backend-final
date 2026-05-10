<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JasaController extends Controller
{
    /**
     * List jasa.
     * - Publik: hanya yang status_tersedia = 'tersedia'
     * - Admin : semua (untuk kelola)
     */
    public function index(Request $request)
    {
        $query = Jasa::query();

        $isAdmin = $request->user() && (bool) $request->user()->admin;
        if (!$isAdmin) {
            $query->where('status_tersedia', 'tersedia');
        }

        $jasa = $query->orderBy('id_jasa', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $jasa->map(fn ($j) => $this->formatJasa($j)),
        ]);
    }

    /**
     * Detail satu jasa.
     */
    public function show($id)
    {
        $jasa = Jasa::find($id);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $this->formatJasa($jasa)]);
    }

    /**
     * Create jasa baru (admin).
     */
    public function store(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), $this->jasaRules(false));
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status_tersedia'] = $data['status_tersedia'] ?? 'tersedia';

        $jasa = Jasa::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jasa berhasil dibuat',
            'data'    => $this->formatJasa($jasa),
        ], 201);
    }

    /**
     * Update jasa (admin).
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $jasa = Jasa::find($id);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), $this->jasaRules(true));
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $jasa->update($validator->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Jasa berhasil diupdate',
            'data'    => $this->formatJasa($jasa->fresh()),
        ]);
    }

    /**
     * Delete jasa (admin).
     * Otomatis cek: jika sudah pernah dipakai pesanan, kasih warning soft-delete (set status_tersedia=tidak_tersedia).
     */
    public function destroy(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $jasa = Jasa::find($id);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }

        // Kalau sudah ada pemesanan terkait → soft delete (sembunyikan dari user, tidak hapus fisik)
        if ($jasa->detailPemesanan()->exists()) {
            $jasa->update(['status_tersedia' => 'tidak_tersedia']);
            return response()->json([
                'status'  => 'success',
                'message' => 'Jasa pernah dipakai pesanan — disembunyikan dari user (soft delete).',
                'data'    => $this->formatJasa($jasa->fresh()),
            ]);
        }

        $jasa->delete();
        return response()->json(['status' => 'success', 'message' => 'Jasa berhasil dihapus permanen']);
    }

    /**
     * Toggle status_tersedia jasa.
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $jasa = Jasa::find($id);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }
        $jasa->status_tersedia = $jasa->status_tersedia === 'tersedia' ? 'tidak_tersedia' : 'tersedia';
        $jasa->save();
        return response()->json([
            'status'  => 'success',
            'message' => 'Status jasa diubah',
            'data'    => $this->formatJasa($jasa),
        ]);
    }

    /* ──────────────────────────────────────────────── */

    private function isAdmin(Request $request): bool
    {
        return $request->user() && (bool) $request->user()->admin;
    }

    private function jasaRules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        $opt = $partial ? 'sometimes|nullable' : 'nullable';
        return [
            'nama_jasa'         => "$req|string|max:255",
            'deskripsi'         => "$req|string",
            'harga'             => "$req|numeric|min:0",
            'status_tersedia'   => "$opt|in:tersedia,tidak_tersedia",
            'icon'              => "$opt|string|max:50",
            'emoji'             => "$opt|string|max:50",
            'tag'               => "$opt|string|max:100",
            'tag_color'         => "$opt|string|max:50",
            'img_bg'            => "$opt|string|max:500",
            'features'          => "$opt|array",
            'features.*'        => 'string|max:200',
            'packages'          => "$opt|array",
            'packages.*.id'     => 'required_with:packages|string|max:50',
            'packages.*.label'  => 'required_with:packages|string|max:100',
            'packages.*.hours'  => 'nullable|string|max:50',
            'packages.*.price'  => 'required_with:packages|numeric|min:0',
            'packages.*.features' => 'nullable|array',
            'addons'            => "$opt|array",
            'addons.*.id'       => 'required_with:addons|string|max:50',
            'addons.*.name'     => 'required_with:addons|string|max:100',
            'addons.*.desc'     => 'nullable|string|max:255',
            'addons.*.price'    => 'required_with:addons|numeric|min:0',
            'addons.*.icon'     => 'nullable|string|max:50',
            'addon_label'       => "$opt|string|max:100",
        ];
    }

    /**
     * Format jasa agar cocok dengan shape ALL_SERVICES + SERVICE_BOOKING di frontend.
     */
    private function formatJasa(Jasa $jasa): array
    {
        return [
            'id'              => $jasa->id_jasa,
            'id_jasa'         => $jasa->id_jasa,
            'title'           => $jasa->nama_jasa,
            'nama_jasa'       => $jasa->nama_jasa,
            'desc'            => $jasa->deskripsi,
            'deskripsi'       => $jasa->deskripsi,
            'harga'           => (float) $jasa->harga,
            'price'           => (float) $jasa->harga,
            'status_tersedia' => $jasa->status_tersedia,
            'icon'            => $jasa->icon  ?: '🎬',
            'emoji'           => $jasa->emoji ?: ($jasa->icon ?: '🎬'),
            'tag'             => $jasa->tag       ?: 'Layanan',
            'tagColor'        => $jasa->tag_color ?: '#1B4FD8',
            'tag_color'       => $jasa->tag_color ?: '#1B4FD8',
            'imgBg'           => $jasa->img_bg ?: 'linear-gradient(135deg,#1a2a6c,#1B4FD8 60%,#23d5ab)',
            'img_bg'          => $jasa->img_bg ?: 'linear-gradient(135deg,#1a2a6c,#1B4FD8 60%,#23d5ab)',
            'features'        => is_array($jasa->features) ? array_values($jasa->features) : [],
            'packages'        => is_array($jasa->packages) ? array_values($jasa->packages) : [],
            'addons'          => is_array($jasa->addons)   ? array_values($jasa->addons)   : [],
            'addonLabel'      => $jasa->addon_label ?: 'Tambahan',
            'addon_label'     => $jasa->addon_label ?: 'Tambahan',
        ];
    }
}
