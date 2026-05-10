<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
