<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekApiSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // [PERBAIKAN] Cek apakah 'api_user' ada di dalam sesi
        if (! $request->session()->has('api_user')) {

            // Jika tidak ada (belum login), paksa kembali ke halaman login
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Jika ada, izinkan request untuk melanjutkan
        return $next($request);
    }
}