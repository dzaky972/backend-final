<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengaturanController extends Controller
{
    /**
     * List semua pengaturan (publik).
     * Output: { kunci: nilai, ... }
     */
    public function index(Request $request)
    {
        $query = Pengaturan::query();
        if ($grup = $request->query('grup')) {
            $query->where('grup', $grup);
        }

        $items = $query->get();

        // Format jadi key-value untuk easy lookup di frontend
        $kv = $items->mapWithKeys(fn ($p) => [$p->kunci => $p->nilai])->toArray();

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
     * Body: { settings: [{ kunci, nilai, grup?, tipe? }, ...] }
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
            Pengaturan::set(
                $s['kunci'],
                $s['nilai'] ?? '',
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
