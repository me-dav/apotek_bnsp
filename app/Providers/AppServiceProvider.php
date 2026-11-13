<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bentuk koneksi: pgsql_login, pgsql_admin_pusat, pgsql_apoteker, pgsql_kasir
        $dasar = config('database.connections.pgsql');

        foreach (config('dbroles') as $nama => $akun) {
            config(["database.connections.pgsql_$nama" => array_merge($dasar, $akun)]);
        }
    }

    public function boot(): void
    {
        // Request web tidak boleh memakai akun owner (hak DDL).
        // Perintah CLI (migrate/seed) tetap memakai akun owner.
        if (!$this->app->runningInConsole()) {
            DB::setDefaultConnection('pgsql_login');
        }
    }
}