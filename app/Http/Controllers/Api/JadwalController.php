<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use App\Models\JasaJadwalBlocked;
use App\Models\Pemesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JadwalController extends Controller
{
    /**
     * Cek ketersediaan tanggal untuk satu jasa.
     * GET /api/jasa/{id}/jadwal?bulan=YYYY-MM
     */
    public function checkJadwal(Request $request, $idJasa)
    {
        $jasa = Jasa::find($idJasa);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }

        // Ambil bulan dari query, default = bulan ini
        $bulan = $request->query('bulan', now()->format('Y-m'));
        try {
            $start = \Carbon\Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
            $end   = (clone $start)->endOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        }

        // ── 1. Tanggal yang di-block admin manual ──
        // Pakai query builder DB:: untuk menghindari masalah cast/relation
        $blockedRows = DB::table('jasa_jadwal_blocked')
            ->where('id_jasa', $idJasa)
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get(['tanggal', 'alasan']);

        $blockedDates = [];
        foreach ($blockedRows as $row) {
            $blockedDates[] = [
                'tanggal' => \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d'),
                'alasan'  => $row->alasan ?: 'Tidak tersedia',
                'tipe'    => 'blocked',
            ];
        }

        // ── 2. Tanggal yang sudah dipesan (status menunggu/proses) ──
        // Pakai DB join langsung untuk menghindari Eloquent relation issues
        $bookedRows = DB::table('pemesanan as p')
            ->join('detail_pemesanan as d', 'd.id_pemesanan', '=', 'p.id_pemesanan')
            ->where('d.id_jasa', $idJasa)
            ->whereIn('p.status_pesanan', ['menunggu', 'proses'])
            ->whereBetween('p.tgl_pelaksanaan', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->select('p.tgl_pelaksanaan')
            ->distinct()
            ->get();

        $bookedDates = [];
        foreach ($bookedRows as $row) {
            $bookedDates[] = [
                'tanggal' => \Carbon\Carbon::parse($row->tgl_pelaksanaan)->format('Y-m-d'),
                'alasan'  => 'Sudah dipesan pelanggan lain',
                'tipe'    => 'booked',
            ];
        }

        // ── 3. Gabungkan tanggal blocked + booked, deduplikasi by tanggal ──
        $unavailableMap = [];
        foreach ($blockedDates as $b) {
            $unavailableMap[$b['tanggal']] = $b;
        }
        foreach ($bookedDates as $b) {
            // Hanya tambah jika belum ada (blocked manual menang)
            if (!isset($unavailableMap[$b['tanggal']])) {
                $unavailableMap[$b['tanggal']] = $b;
            }
        }
        $allUnavailable = array_values($unavailableMap);
        $unavailableList = array_keys($unavailableMap);
        sort($unavailableList);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id_jasa'          => (int) $idJasa,
                'bulan'            => $start->format('Y-m'),
                'blocked_dates'    => $blockedDates,
                'booked_dates'     => $bookedDates,
                'unavailable'      => $allUnavailable,
                'unavailable_list' => $unavailableList,
            ],
        ]);
    }

    /**
     * ADMIN: List semua tanggal yang di-block untuk satu jasa.
     */
    public function listBlocked(Request $request, $idJasa)
    {
        $jasa = Jasa::find($idJasa);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }

        // Pakai DB:: + leftJoin untuk hindari masalah eager loading
        $rows = DB::table('jasa_jadwal_blocked as b')
            ->leftJoin('users as u', 'u.id_user', '=', 'b.blocked_by')
            ->where('b.id_jasa', $idJasa)
            ->orderBy('b.tanggal', 'asc')
            ->select(
                'b.id_blocked',
                'b.tanggal',
                'b.alasan',
                'b.created_at',
                'u.nama as admin_nama'
            )
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id_blocked' => (int) $row->id_blocked,
                'tanggal'    => \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d'),
                'alasan'     => $row->alasan,
                'admin'      => $row->admin_nama,
                'created_at' => $row->created_at,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * ADMIN: Block satu tanggal untuk jasa.
     */
    public function blockDate(Request $request, $idJasa)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date|after_or_equal:today',
            'alasan'  => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $jasa = Jasa::find($idJasa);
        if (!$jasa) {
            return response()->json(['status' => 'error', 'message' => 'Jasa tidak ditemukan'], 404);
        }

        $tanggal = $request->tanggal;

        // Cek apakah sudah ada
        $existing = DB::table('jasa_jadwal_blocked')
            ->where('id_jasa', $idJasa)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tanggal ini sudah di-block sebelumnya.',
            ], 422);
        }

        $blocked = JasaJadwalBlocked::create([
            'id_jasa'    => $idJasa,
            'tanggal'    => $tanggal,
            'alasan'     => $request->alasan,
            'blocked_by' => $request->user()->id_user,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Tanggal berhasil di-block',
            'data'    => [
                'id_blocked' => $blocked->id_blocked,
                'tanggal'    => \Carbon\Carbon::parse($blocked->tanggal)->format('Y-m-d'),
                'alasan'     => $blocked->alasan,
            ],
        ], 201);
    }

    /**
     * ADMIN: Hapus block tanggal.
     */
    public function unblockDate(Request $request, $idBlocked)
    {
        $blocked = JasaJadwalBlocked::find($idBlocked);
        if (!$blocked) {
            return response()->json(['status' => 'error', 'message' => 'Block tidak ditemukan'], 404);
        }

        $blocked->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Tanggal kembali tersedia',
        ]);
    }
}
