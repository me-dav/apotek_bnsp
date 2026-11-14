<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PakaiKoneksiRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $koneksi = 'pgsql_' . $user->role;

        abort_unless(config("database.connections.$koneksi"), 403);

        // Mulai sini semua query memakai akun database sesuai peran
        DB::setDefaultConnection($koneksi);

        // Model user dimuat middleware `auth` lewat pgsql_login dan menyimpan koneksi itu.
        // Pindahkan juga, agar relasinya ($user->cabang, dst.) ikut memakai akun peran.
        $user->setConnection($koneksi);

        return $next($request);
        
    }
}