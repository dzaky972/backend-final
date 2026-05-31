<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register PELANGGAN baru saja.
     * Admin TIDAK BISA register dari endpoint publik — admin di-seed manual.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6',
            'no_telp'    => 'nullable|string|max:30',
            'alamat'     => 'nullable|string|max:500',
            'perusahaan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'no_telp'  => $request->no_telp,
        ]);

        // Selalu buat record pelanggan untuk setiap user yang register dari publik
        Pelanggan::create([
            'id_user'    => $user->id_user,
            'alamat'     => $request->alamat,
            'perusahaan' => $request->perusahaan,
        ]);

        $user->load('pelanggan', 'admin');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Registrasi berhasil',
            'data' => [
                'user'  => $this->formatUser($user),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login universal (admin & pelanggan dengan endpoint sama).
     * Frontend yang memutuskan apakah user boleh masuk ke /admin atau / berdasarkan field `role`.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        $user->load('pelanggan', 'admin');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user'  => $this->formatUser($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Endpoint /me — dipanggil saat refresh untuk auto-login.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('pelanggan', 'admin');
        return response()->json([
            'status' => 'success',
            'data'   => $this->formatUser($user),
        ]);
    }

    /**
     * Update profil. Admin hanya boleh ubah nama/email/no_telp (TIDAK ada alamat/perusahaan).
     * Pelanggan boleh ubah semua.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $user->load('pelanggan', 'admin');

        $rules = [
            'nama'    => 'sometimes|string|max:255',
            'email'   => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id_user, 'id_user')],
            'no_telp' => 'sometimes|nullable|string|max:30',
        ];

        // Hanya pelanggan yang boleh edit alamat & perusahaan
        if (!$user->isAdmin()) {
            $rules['alamat']     = 'sometimes|nullable|string|max:500';
            $rules['perusahaan'] = 'sometimes|nullable|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->fill($request->only(['nama', 'email', 'no_telp']));
        $user->save();

        // Update record pelanggan HANYA kalau user ini pelanggan
        if (!$user->isAdmin() && ($request->has('alamat') || $request->has('perusahaan'))) {
            $pelanggan = $user->pelanggan;
            if (!$pelanggan) {
                $pelanggan = Pelanggan::create([
                    'id_user'    => $user->id_user,
                    'alamat'     => $request->alamat,
                    'perusahaan' => $request->perusahaan,
                ]);
            } else {
                $pelanggan->update($request->only(['alamat', 'perusahaan']));
            }
        }

        $user->load('pelanggan', 'admin');

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil diperbarui',
            'data'    => $this->formatUser($user),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama salah',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * POST /api/forgot-password
     *
     * User submit email -> backend generate token, simpan ke password_reset_tokens,
     * lalu kirim email berisi link reset (lewat Mailtrap di environment dev).
     *
     * Selalu return sukses untuk mencegah email enumeration attack
     * (attacker tidak bisa tahu email mana yang terdaftar/tidak).
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Email tidak valid', 'errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->email));
        $user  = User::where('email', $email)->first();

        // Kalau user ada -> generate token & kirim email
        if ($user) {
            $token  = Str::random(64);
            $hashed = hash('sha256', $token);

            // Upsert ke password_reset_tokens (1 token aktif per email)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => $hashed,
                    'created_at' => Carbon::now(),
                ]
            );

            // Compose reset URL untuk frontend
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
            $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);

            // Kirim email - fail silently kalau Mailtrap belum dikonfigurasi
            try {
                Mail::to($email)->send(new ResetPasswordMail(
                    userName:   $user->nama ?: 'Pengguna',
                    resetUrl:   $resetUrl,
                    expiryMins: 60,
                ));
            } catch (\Throwable $e) {
                Log::error('Gagal kirim email reset password: ' . $e->getMessage());
            }
        }

        // Return sukses APAPUN hasilnya (anti email enumeration)
        return response()->json([
            'status'  => 'success',
            'message' => 'Jika email terdaftar, link reset password akan dikirim ke email Anda. Silakan cek inbox (dan folder spam).',
        ]);
    }

    /**
     * POST /api/reset-password
     *
     * User submit token + email + password baru.
     * Backend validasi token (sha256 match + belum expired) -> update password.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email|max:255',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            // Rule 'confirmed' butuh field 'password_confirmation' di request
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->email));

        // Cek record token
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token reset password tidak ditemukan atau sudah pernah digunakan.',
            ], 422);
        }

        // Verifikasi token (hash sha256)
        $hashedInput = hash('sha256', $request->token);
        if (!hash_equals($record->token, $hashedInput)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token tidak valid.',
            ], 422);
        }

        // Cek expiry (60 menit dari created_at)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            // Token kadaluwarsa - hapus & error
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'status'  => 'error',
                'message' => 'Link reset password sudah kedaluwarsa. Silakan minta link baru.',
            ], 422);
        }

        // Cek user masih ada
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        // Update password & hapus token (single-use)
        DB::transaction(function () use ($user, $request, $email) {
            $user->password = Hash::make($request->password);
            $user->save();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil direset. Silakan masuk dengan password baru Anda.',
        ]);
    }

    /**
     * Format user untuk response. Tegas bedakan role admin vs pelanggan.
     */
    private function formatUser(User $user): array
    {
        $nameParts = explode(' ', trim($user->nama));
        $avatar = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? '', 0, 1));

        $isAdmin     = $user->isAdmin();
        $isPelanggan = $user->isPelanggan();
        $role        = $isAdmin ? 'admin' : ($isPelanggan ? 'pelanggan' : 'guest');

        return [
            'id'           => $user->id_user,
            'id_user'      => $user->id_user,
            'name'         => $user->nama,
            'nama'         => $user->nama,
            'email'        => $user->email,
            'phone'        => $user->no_telp ?: '-',
            'no_telp'      => $user->no_telp,
            // Admin TIDAK punya alamat/perusahaan dari sisi data pelanggan
            'alamat'       => $isAdmin ? null : ($user->pelanggan?->alamat),
            'company'      => $isAdmin ? 'IMA Creative Production' : ($user->pelanggan?->perusahaan ?: ''),
            'perusahaan'   => $isAdmin ? null : ($user->pelanggan?->perusahaan),
            'avatar'       => $avatar ?: 'U',
            'role'         => $role,
            'role_level'   => $isAdmin ? ($user->admin?->role_level ?? 'admin') : null,
            'is_admin'     => $isAdmin,
            'is_pelanggan' => $isPelanggan,
        ];
    }
}