<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\JasaController;
use App\Http\Controllers\Api\PemesananController;
use App\Http\Controllers\Api\PengaturanController;
use App\Http\Controllers\Api\PortofolioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - IMA Creative Production
|
| Pemisahan role:
|   - PUBLIK     : tanpa auth (login, register, lihat jasa, webhook midtrans)
|   - PELANGGAN  : harus login + bukan admin (pesan jasa, profile, bayar)
|   - ADMIN      : harus login + admin (kelola jasa, pesanan, laporan)
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/ping', fn () => response()->json(['status' => 'ok', 'time' => now()]));

// ─────────────────────────────────────────────────────────
//  PUBLIK — tanpa auth
// ─────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/jasa',                  [JasaController::class, 'index']);
Route::get('/jasa/{id}',             [JasaController::class, 'show']);
Route::get('/jasa/{id}/jadwal',      [JadwalController::class, 'checkJadwal']); // cek tanggal available

Route::get('/portofolio',            [PortofolioController::class, 'index']);
Route::get('/portofolio/{id}',       [PortofolioController::class, 'show']);

Route::get('/pengaturan',            [PengaturanController::class, 'index']);

// Webhook Midtrans (tanpa auth — di-validate signature)
Route::post('/midtrans/notification', [PemesananController::class, 'midtransNotification']);

// ─────────────────────────────────────────────────────────
//  AUTH (Sanctum) — semua user yang login
// ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Universal — admin & pelanggan sama-sama bisa
    Route::get('/me',       [AuthController::class, 'me']);
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::put('/profile',  [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);

    // ─────────────────────────────────────────────────────
    //  ROUTE PEMESANAN — pelanggan only (admin diblokir di controller)
    // ─────────────────────────────────────────────────────
    Route::get('/pemesanan',                 [PemesananController::class, 'index']);
    Route::get('/pemesanan/{id}',            [PemesananController::class, 'show']);
    Route::post('/pemesanan',                [PemesananController::class, 'store']);
    Route::get('/pemesanan/{id}/status',     [PemesananController::class, 'paymentStatus']);
    Route::post('/pemesanan/{id}/bayar',     [PemesananController::class, 'createPayment']);
    Route::post('/pemesanan/{id}/cancel',    [PemesananController::class, 'cancel']);

    // ─────────────────────────────────────────────────────
    //  ROUTE ADMIN — wajib admin (closure middleware)
    // ─────────────────────────────────────────────────────
    Route::prefix('admin')->middleware([\App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
        Route::get('/dashboard',   [AdminController::class, 'dashboard']);
        Route::get('/pelanggan',   [AdminController::class, 'listPelanggan']);
        Route::get('/laporan',     [AdminController::class, 'laporan']);

        // CRUD Jasa
        Route::get('/jasa',              [JasaController::class, 'index']);
        Route::post('/jasa',             [JasaController::class, 'store']);
        Route::put('/jasa/{id}',         [JasaController::class, 'update']);
        Route::delete('/jasa/{id}',      [JasaController::class, 'destroy']);
        Route::post('/jasa/{id}/toggle', [JasaController::class, 'toggleStatus']);

        // Jadwal blocked dates (admin)
        Route::get('/jasa/{id}/blocked',           [JadwalController::class, 'listBlocked']);
        Route::post('/jasa/{id}/block',            [JadwalController::class, 'blockDate']);
        Route::delete('/blocked/{id_blocked}',     [JadwalController::class, 'unblockDate']);

        // CRUD Portofolio
        Route::post('/portofolio',          [PortofolioController::class, 'store']);
        Route::put('/portofolio/{id}',      [PortofolioController::class, 'update']);
        Route::delete('/portofolio/{id}',   [PortofolioController::class, 'destroy']);

        // Pengaturan beranda
        Route::put('/pengaturan',           [PengaturanController::class, 'updateBatch']);

        // Update status pesanan
        Route::put('/pemesanan/{id}/status', [PemesananController::class, 'updateStatus']);
    });
});
