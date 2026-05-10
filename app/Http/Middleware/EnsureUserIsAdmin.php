<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Middleware untuk memastikan user yang mengakses adalah admin.
     * Dipakai di route /api/admin/*
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda belum login.',
            ], 401);
        }

        $user->load('admin');

        if (!$user->isAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Halaman ini hanya untuk admin.',
            ], 403);
        }

        return $next($request);
    }
}
