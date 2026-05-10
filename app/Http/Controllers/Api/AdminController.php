<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use App\Models\Pemesanan;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function checkAdmin(Request $request)
    {
        if (!$request->user() || !$request->user()->admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        return null;
    }

    /**
     * Statistik dashboard.
     */
    public function dashboard(Request $request)
    {
        if ($r = $this->checkAdmin($request)) return $r;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_pelanggan'     => User::whereHas('pelanggan')->count(),
                'total_jasa'          => Jasa::count(),
                'total_jasa_aktif'    => Jasa::where('status_tersedia', 'tersedia')->count(),
                'total_pemesanan'     => Pemesanan::count(),
                'pemesanan_menunggu'  => Pemesanan::where('status_pesanan', 'menunggu')->count(),
                'pemesanan_proses'    => Pemesanan::where('status_pesanan', 'proses')->count(),
                'pemesanan_selesai'   => Pemesanan::where('status_pesanan', 'selesai')->count(),
                'pemesanan_batal'     => Pemesanan::where('status_pesanan', 'batal')->count(),
                'pendapatan_total'    => (float) Pembayaran::where('status_verifikasi', 'success')->sum('jumlah'),
                'pendapatan_bulan_ini'=> (float) Pembayaran::where('status_verifikasi', 'success')
                    ->whereMonth('tgl_bayar', now()->month)
                    ->whereYear('tgl_bayar', now()->year)
                    ->sum('jumlah'),
            ],
        ]);
    }

    /**
     * List semua pelanggan.
     */
    public function listPelanggan(Request $request)
    {
        if ($r = $this->checkAdmin($request)) return $r;

        $pelanggan = User::with('pelanggan')
            ->whereHas('pelanggan')
            ->withCount(['pemesanan as total_pesanan'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($u) => [
                'id'             => $u->id_user,
                'id_user'        => $u->id_user,
                'nama'           => $u->nama,
                'email'          => $u->email,
                'no_telp'        => $u->no_telp,
                'alamat'         => $u->pelanggan?->alamat,
                'perusahaan'     => $u->pelanggan?->perusahaan,
                'total_pesanan'  => (int) ($u->total_pesanan ?? 0),
                'bergabung'      => $u->created_at?->toIso8601String(),
            ]);

        return response()->json(['status' => 'success', 'data' => $pelanggan]);
    }

    /**
     * Laporan pesanan (filter rentang tanggal).
     */
    public function laporan(Request $request)
    {
        if ($r = $this->checkAdmin($request)) return $r;

        $dari   = $request->query('dari');
        $sampai = $request->query('sampai');

        $query = Pemesanan::with(['details.jasa', 'pembayaran', 'user'])
            ->orderBy('tgl_pemesanan', 'desc');

        if ($dari)   $query->whereDate('tgl_pemesanan', '>=', $dari);
        if ($sampai) $query->whereDate('tgl_pemesanan', '<=', $sampai);

        $pesanan = $query->get();

        $rekap = [
            'total_pesanan'       => $pesanan->count(),
            'total_pendapatan'    => (float) $pesanan->sum('total_harga'),
            'pendapatan_lunas'    => (float) $pesanan->filter(fn ($p) => $p->pembayaran?->status_verifikasi === 'success')->sum('total_harga'),
            'pendapatan_menunggu' => (float) $pesanan->filter(fn ($p) => $p->pembayaran?->status_verifikasi !== 'success')->sum('total_harga'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'rekap'   => $rekap,
                'pesanan' => $pesanan->map(fn ($p) => [
                    'kode_pemesanan'  => $p->kode_pemesanan,
                    'tanggal'         => $p->tgl_pemesanan?->toIso8601String(),
                    'pelaksanaan'     => $p->tgl_pelaksanaan?->toIso8601String(),
                    'pelanggan'       => $p->user?->nama,
                    'perusahaan'      => $p->perusahaan,
                    'jasa'            => $p->details->pluck('jasa.nama_jasa')->filter()->values(),
                    'total'           => (float) $p->total_harga,
                    'status'          => $p->status_pesanan,
                    'status_bayar'    => $p->pembayaran?->status_verifikasi ?? 'pending',
                ]),
            ],
        ]);
    }
}
