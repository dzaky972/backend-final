<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use App\Models\Pemesanan;
use App\Models\DetailPemesanan;
use App\Models\Pembayaran;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PemesananController extends Controller
{
    /**
     * List pesanan.
     * - Pelanggan: hanya pesanan miliknya
     * - Admin    : semua pesanan
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = (bool) $user->admin;

        $query = Pemesanan::with(['details.jasa', 'pembayaran', 'user.pelanggan'])
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            $query->where('id_pelanggan', $user->id_user);
        }

        if ($status = $request->query('status')) {
            if ($status !== 'semua') {
                $query->where('status_pesanan', $status);
            }
        }

        $pesanan = $query->get();

        return response()->json([
            'status' => 'success',
            'data'   => $pesanan->map(fn ($p) => $this->formatPemesanan($p)),
        ]);
    }

    /**
     * Detail satu pesanan.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $pesanan = Pemesanan::with(['details.jasa', 'pembayaran', 'user.pelanggan'])->find($id);

        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }

        $isAdmin = (bool) $user->admin;
        if (!$isAdmin && $pesanan->id_pelanggan !== $user->id_user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatPemesanan($pesanan),
        ]);
    }

    /**
     * Buat pesanan baru.
     * Server menghitung ulang harga (anti-tampering dari frontend).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $user->load('admin', 'pelanggan');

        // ─── BLOCK ADMIN ───
        // Admin tidak boleh memesan jasa. Pemesanan hanya untuk pelanggan.
        if ($user->isAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun admin tidak dapat melakukan pemesanan. Pemesanan hanya untuk akun pelanggan.',
            ], 403);
        }

        // ─── PASTIKAN PUNYA RECORD PELANGGAN ───
        if (!$user->pelanggan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda belum terdaftar sebagai pelanggan. Silakan lengkapi profil Anda terlebih dahulu.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_jasa'           => 'required|integer|exists:jasa,id_jasa',
            'paket_id'          => 'required|string|max:50',
            'paket_label'       => 'nullable|string|max:100',
            'addons'            => 'nullable|array',
            'addons.*.id'       => 'required_with:addons|string|max:50',
            'addons.*.name'     => 'nullable|string|max:100',
            'addons.*.price'    => 'nullable|numeric|min:0',
            'addons.*.quantity' => 'nullable|integer|min:1',
            'tgl_pelaksanaan'   => 'required|date|after_or_equal:today',
            'waktu_pelaksanaan' => 'required|string|max:20',
            'nama_pic'          => 'required|string|max:255',
            'telepon_pic'       => 'required|string|max:30',
            'perusahaan'        => 'nullable|string|max:255',
            'catatan'           => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $jasa = Jasa::find($data['id_jasa']);

        if ($jasa->status_tersedia !== 'tersedia') {
            return response()->json(['status' => 'error', 'message' => 'Jasa ini sedang tidak tersedia.'], 422);
        }

        // ── Cek apakah tanggal di-block oleh admin ──
        $isBlocked = \App\Models\JasaJadwalBlocked::where('id_jasa', $data['id_jasa'])
            ->whereDate('tanggal', $data['tgl_pelaksanaan'])
            ->exists();
        if ($isBlocked) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tanggal yang Anda pilih tidak tersedia. Silakan pilih tanggal lain.',
            ], 422);
        }

        // ── Cek apakah tanggal sudah dipesan (status menunggu/proses) ──
        $isBooked = Pemesanan::whereHas('details', fn ($q) => $q->where('id_jasa', $data['id_jasa']))
            ->whereIn('status_pesanan', ['menunggu', 'proses'])
            ->whereDate('tgl_pelaksanaan', $data['tgl_pelaksanaan'])
            ->exists();
        if ($isBooked) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tanggal ini sudah dipesan oleh pelanggan lain. Silakan pilih tanggal yang berbeda.',
            ], 422);
        }

        // ── Server-side price calculation ──
        $packages = is_array($jasa->packages) ? $jasa->packages : [];
        $addonsConf = is_array($jasa->addons) ? $jasa->addons : [];

        $paket = collect($packages)->firstWhere('id', $data['paket_id']);
        if (!$paket) {
            return response()->json(['status' => 'error', 'message' => 'Paket tidak ditemukan untuk jasa ini'], 422);
        }
        $paketPrice = (float) ($paket['price'] ?? 0);
        $paketLabel = $data['paket_label'] ?? ($paket['label'] ?? null);

        $addonTotal = 0;
        $addonsClean = [];
        foreach ($data['addons'] ?? [] as $a) {
            $found = collect($addonsConf)->firstWhere('id', $a['id']);
            if (!$found) continue;
            $qty = max(1, (int) ($a['quantity'] ?? 1));
            $price = (float) ($found['price'] ?? 0);
            $addonTotal += $price * $qty;
            $addonsClean[] = [
                'id'       => $found['id'],
                'name'     => $found['name'] ?? $found['id'],
                'price'    => $price,
                'quantity' => $qty,
                'icon'     => $found['icon'] ?? null,
            ];
        }

        $subtotal = $paketPrice + $addonTotal;
        $tax      = (int) round($subtotal * 0.11);
        $total    = $subtotal + $tax;

        $pemesanan = DB::transaction(function () use ($user, $data, $jasa, $paketLabel, $addonsClean, $subtotal, $total) {
            // Generate kode unik (cek collision)
            do {
                $kode = 'IMA-' . str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            } while (Pemesanan::where('kode_pemesanan', $kode)->exists());

            $pesanan = Pemesanan::create([
                'kode_pemesanan'    => $kode,
                'id_pelanggan'      => $user->id_user,
                'tgl_pemesanan'     => now(),
                'tgl_pelaksanaan'   => $data['tgl_pelaksanaan'],
                'waktu_pelaksanaan' => $data['waktu_pelaksanaan'],
                'total_harga'       => $total,
                'status_pesanan'    => 'menunggu',
                // sub_status_pesanan: tidak diisi karena status awal = 'menunggu'
                'nama_pic'          => $data['nama_pic'],
                'telepon_pic'       => $data['telepon_pic'],
                'perusahaan'        => $data['perusahaan'] ?? null,
                'catatan'           => $data['catatan'] ?? null,
            ]);

            DetailPemesanan::create([
                'id_pemesanan' => $pesanan->id_pemesanan,
                'id_jasa'      => $jasa->id_jasa,
                'paket_id'     => $data['paket_id'],
                'paket_label'  => $paketLabel,
                'addons'       => $addonsClean,
                'kuantitas'    => 1,
                'subtotal'     => $subtotal,
            ]);

            Pembayaran::create([
                'id_pemesanan'      => $pesanan->id_pemesanan,
                'jumlah'            => $total,
                'status_verifikasi' => 'pending',
            ]);

            return $pesanan;
        });

        $pemesanan->load(['details.jasa', 'pembayaran', 'user.pelanggan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pesanan berhasil dibuat',
            'data'    => $this->formatPemesanan($pemesanan),
        ], 201);
    }

    /**
     * Update status pesanan (admin).
     *
     * MENERIMA 2 FIELD:
     *   - status_pesanan (required)     : menunggu|proses|selesai|batal
     *   - sub_status_pesanan (optional) : dikonfirmasi|persiapan|berlangsung|acara_selesai
     *
     * LOGIKA:
     *   - Kalau status_pesanan='proses' → sub_status WAJIB ada (default 'dikonfirmasi')
     *   - Kalau status_pesanan bukan 'proses' → sub_status di-reset ke null
     */
    public function updateStatus(Request $request, $id)
    {
        if (!$request->user() || !$request->user()->admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status_pesanan'     => 'required|in:menunggu,proses,selesai,batal',
            'sub_status_pesanan' => 'nullable|in:dikonfirmasi,persiapan,berlangsung,acara_selesai',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $pesanan = Pemesanan::find($id);
        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }

        $pesanan->status_pesanan = $request->status_pesanan;

        // Logika sub_status:
        if ($request->status_pesanan === 'proses') {
            // Kalau status='proses', sub_status wajib ada → default ke 'dikonfirmasi'
            $pesanan->sub_status_pesanan = $request->sub_status_pesanan ?? 'dikonfirmasi';
        } else {
            // Untuk status menunggu/selesai/batal → reset sub_status ke null
            $pesanan->sub_status_pesanan = null;
        }

        $pesanan->save();
        $pesanan->load(['details.jasa', 'pembayaran', 'user.pelanggan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status pesanan diperbarui',
            'data'    => $this->formatPemesanan($pesanan),
        ]);
    }

    /**
     * Pelanggan membatalkan pesanannya sendiri.
     * Hanya bisa kalau status masih 'menunggu' dan pembayaran belum sukses.
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $user->load('admin');

        // Admin gunakan endpoint /admin/pemesanan/{id}/status, bukan endpoint cancel ini.
        if ($user->isAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Admin tidak dapat membatalkan pesanan dari endpoint ini. Gunakan menu update status.',
            ], 403);
        }

        $pesanan = Pemesanan::with('pembayaran')->find($id);

        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }
        if ($pesanan->id_pelanggan !== $user->id_user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        if ($pesanan->status_pesanan !== 'menunggu') {
            return response()->json(['status' => 'error', 'message' => 'Pesanan ini sudah tidak bisa dibatalkan.'], 422);
        }
        if ($pesanan->pembayaran && $pesanan->pembayaran->status_verifikasi === 'success') {
            return response()->json(['status' => 'error', 'message' => 'Pembayaran sudah berhasil — tidak bisa dibatalkan.'], 422);
        }

        $pesanan->status_pesanan = 'batal';
        $pesanan->sub_status_pesanan = null;
        $pesanan->save();
        $pesanan->load(['details.jasa', 'pembayaran', 'user.pelanggan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pesanan dibatalkan',
            'data'    => $this->formatPemesanan($pesanan),
        ]);
    }

    /**
     * Buat Snap Token Midtrans.
     */
    public function createPayment(Request $request, $id, MidtransService $midtrans)
    {
        $user = $request->user();
        $user->load('admin');

        if ($user->isAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun admin tidak dapat melakukan pembayaran.',
            ], 403);
        }

        $pesanan = Pemesanan::with(['details.jasa', 'pembayaran'])->find($id);

        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }
        if ($pesanan->id_pelanggan !== $user->id_user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        if ($pesanan->status_pesanan === 'batal') {
            return response()->json(['status' => 'error', 'message' => 'Pesanan sudah dibatalkan.'], 422);
        }

        $serverKey = config('midtrans.server_key');
        if (empty($serverKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Midtrans belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY & MIDTRANS_CLIENT_KEY di .env backend.',
            ], 500);
        }

        $pembayaran = $pesanan->pembayaran ?? Pembayaran::create([
            'id_pemesanan'      => $pesanan->id_pemesanan,
            'jumlah'            => $pesanan->total_harga,
            'status_verifikasi' => 'pending',
        ]);

        // order_id unik per attempt → bisa retry
        $orderId = $pesanan->kode_pemesanan . '-' . time();

        $items = [];
        foreach ($pesanan->details as $d) {
            $items[] = [
                'id'       => 'JS-' . $d->id_jasa,
                'price'    => (int) $d->subtotal,
                'quantity' => (int) $d->kuantitas,
                'name'     => substr(($d->jasa->nama_jasa ?? 'Jasa') . ' - ' . ($d->paket_label ?? ''), 0, 50),
            ];
        }
        $subtotalItems = array_sum(array_column($items, 'price'));
        $ppn = (int) $pesanan->total_harga - $subtotalItems;
        if ($ppn > 0) {
            $items[] = ['id' => 'PPN', 'price' => $ppn, 'quantity' => 1, 'name' => 'PPN 11%'];
        }

        try {
            $result = $midtrans->createSnapTransaction([
                'order_id'     => $orderId,
                'gross_amount' => (int) $pesanan->total_harga,
                'customer' => [
                    'first_name' => $pesanan->nama_pic ?: $user->nama,
                    'email'      => $user->email,
                    'phone'      => $pesanan->telepon_pic ?: ($user->no_telp ?? ''),
                ],
                'items'      => $items,
                'finish_url' => config('app.frontend_url', 'http://localhost:5173') . '/?payment=finish',
            ]);

            $pembayaran->update([
                'midtrans_order_id'   => $orderId,
                'midtrans_snap_token' => $result['snap_token'],
                'metode_bayar'        => 'midtrans',
                'status_verifikasi'   => 'pending',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Snap token berhasil dibuat',
                'data' => [
                    'snap_token'     => $result['snap_token'],
                    'redirect_url'   => $result['redirect_url'],
                    'client_key'     => $result['client_key'],
                    'order_id'       => $orderId,
                    'kode_pemesanan' => $pesanan->kode_pemesanan,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook Midtrans (tanpa auth).
     * Saat pembayaran sukses → otomatis set status='proses' & sub_status='dikonfirmasi'.
     */
    public function midtransNotification(Request $request, MidtransService $midtrans)
    {
        $notification = $request->all();

        if (!$midtrans->verifySignature($notification)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        $orderId = $notification['order_id'] ?? null;
        $pembayaran = Pembayaran::where('midtrans_order_id', $orderId)->first();
        if (!$pembayaran) {
            return response()->json(['status' => 'error', 'message' => 'Pembayaran tidak ditemukan'], 404);
        }

        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus       = $notification['fraud_status'] ?? null;
        $paymentType       = $notification['payment_type'] ?? null;
        $transactionId     = $notification['transaction_id'] ?? null;

        $newStatus = match (true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'success',
            $transactionStatus === 'settlement' => 'success',
            $transactionStatus === 'pending'    => 'pending',
            $transactionStatus === 'deny'       => 'failed',
            $transactionStatus === 'expire'     => 'expired',
            $transactionStatus === 'cancel'     => 'cancel',
            default                             => $transactionStatus,
        };

        $pembayaran->update([
            'status_verifikasi'       => $newStatus,
            'midtrans_transaction_id' => $transactionId,
            'midtrans_payment_type'   => $paymentType,
            'midtrans_fraud_status'   => $fraudStatus,
            'midtrans_response'       => $notification,
            'tgl_bayar'               => $newStatus === 'success' ? now() : $pembayaran->tgl_bayar,
        ]);

        if ($newStatus === 'success') {
            $pemesanan = $pembayaran->pemesanan;
            if ($pemesanan && $pemesanan->status_pesanan === 'menunggu') {
                $pemesanan->status_pesanan      = 'proses';
                $pemesanan->sub_status_pesanan  = 'dikonfirmasi'; // ← Default setelah pembayaran berhasil
                $pemesanan->save();
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Cek status pembayaran (polling friendly).
     */
    public function paymentStatus(Request $request, $id)
    {
        $user = $request->user();
        $pesanan = Pemesanan::with('pembayaran')->find($id);

        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }
        if ($pesanan->id_pelanggan !== $user->id_user && !$user->admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'status_pesanan'    => $pesanan->status_pesanan,
                'status_pembayaran' => $pesanan->pembayaran?->status_verifikasi ?? 'pending',
                'metode_bayar'      => $pesanan->pembayaran?->metode_bayar,
                'tgl_bayar'         => $pesanan->pembayaran?->tgl_bayar,
            ],
        ]);
    }

    /* ──────────────────────────────────────────────── */

    private function formatPemesanan(Pemesanan $p): array
    {
        $firstDetail = $p->details->first();
        $jasaId      = $firstDetail?->id_jasa;
        $jasaNama    = $firstDetail?->jasa?->nama_jasa ?? '-';
        $paketLabel  = $firstDetail?->paket_label ?? '-';

        $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $tgl = $p->tgl_pelaksanaan;
        $dateLabel = $tgl ? ($tgl->day . ' ' . $bulan[$tgl->month - 1] . ' ' . $tgl->year) : '-';

        return [
            // dual key untuk kompatibilitas
            'id'             => $p->kode_pemesanan,        // string code (frontend lama)
            'id_pemesanan'   => $p->id_pemesanan,          // numeric (backend)
            'kode'           => $p->kode_pemesanan,
            'kode_pemesanan' => $p->kode_pemesanan,

            'svc'            => $jasaId,
            'svcName'        => $jasaNama,
            'nama_jasa'      => $jasaNama,
            'paket'          => $paketLabel,
            'paket_label'    => $paketLabel,

            'date'           => $dateLabel,
            'time'           => $p->waktu_pelaksanaan,
            'tgl_pelaksanaan_raw' => $p->tgl_pelaksanaan?->toDateString(),
            'waktu_pelaksanaan'   => $p->waktu_pelaksanaan,

            'total'              => (float) $p->total_harga,
            'total_harga'        => (float) $p->total_harga,
            'status'             => $p->status_pesanan,
            'status_pesanan'     => $p->status_pesanan,
            // ── Field BARU untuk status detail ──────────────────
            'sub_status'         => $p->sub_status_pesanan,
            'sub_status_pesanan' => $p->sub_status_pesanan,
            'display_status'     => $this->computeDisplayStatus($p), // key gabungan untuk frontend
            // ─────────────────────────────────────────────────────

            'company'        => $p->perusahaan ?? '-',
            'perusahaan'     => $p->perusahaan,
            'name'           => $p->nama_pic,
            'nama_pic'       => $p->nama_pic,
            'phone'          => $p->telepon_pic,
            'telepon_pic'    => $p->telepon_pic,
            'notes'          => $p->catatan,
            'catatan'        => $p->catatan,
            'tgl_pemesanan'  => $p->tgl_pemesanan?->toIso8601String(),

            'details' => $p->details->map(fn ($d) => [
                'id_detail'   => $d->id_detail,
                'id_jasa'     => $d->id_jasa,
                'nama_jasa'   => $d->jasa?->nama_jasa,
                'paket_id'    => $d->paket_id,
                'paket_label' => $d->paket_label,
                'addons'      => $d->addons ?? [],
                'kuantitas'   => $d->kuantitas,
                'subtotal'    => (float) $d->subtotal,
            ]),

            'pembayaran' => $p->pembayaran ? [
                'id_pembayaran'     => $p->pembayaran->id_pembayaran,
                'metode_bayar'      => $p->pembayaran->metode_bayar,
                'status_verifikasi' => $p->pembayaran->status_verifikasi,
                'jumlah'            => (float) $p->pembayaran->jumlah,
                'tgl_bayar'         => $p->pembayaran->tgl_bayar?->toIso8601String(),
                'snap_token'        => $p->pembayaran->midtrans_snap_token,
                'order_id'          => $p->pembayaran->midtrans_order_id,
            ] : null,

            'customer' => $p->user ? [
                'id'         => $p->user->id_user,
                'nama'       => $p->user->nama,
                'email'      => $p->user->email,
                'no_telp'    => $p->user->no_telp,
                'perusahaan' => $p->user->pelanggan?->perusahaan,
            ] : null,
        ];
    }

    /**
     * Hitung "display status" — key yang dipakai frontend untuk render badge/label.
     * Hasilnya salah satu dari 7 nilai:
     *   menunggu_pembayaran | dikonfirmasi | persiapan | berlangsung |
     *   acara_selesai | selesai | batal
     */
    private function computeDisplayStatus(Pemesanan $p): string
    {
        $status    = $p->status_pesanan;
        $subStatus = $p->sub_status_pesanan;

        if ($status === 'batal')   return 'batal';
        if ($status === 'selesai') return 'selesai';

        if ($status === 'menunggu') {
            // Cek pembayaran: kalau sudah success (jarang terjadi karena webhook
            // biasanya sudah ngeset status ke 'proses', tapi handle untuk safety)
            $payStatus = $p->pembayaran?->status_verifikasi;
            if ($payStatus === 'success') return 'dikonfirmasi';
            return 'menunggu_pembayaran';
        }

        // status_pesanan === 'proses' → mapping dari sub_status
        if ($status === 'proses') {
            return $subStatus ?: 'dikonfirmasi';
        }

        return 'menunggu_pembayaran';
    }
}