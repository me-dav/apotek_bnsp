<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalankan ulang setiap selesai migrasi: tabel/procedure baru butuh GRANT, dan
// routine baru otomatis bisa dieksekusi PUBLIC sampai REVOKE dijalankan lagi.
Artisan::command('db:roles', function () {
    // CLI memakai koneksi owner (lihat AppServiceProvider), pemilik semua objek
    DB::unprepared(file_get_contents(database_path('sql/roles.sql')));
    $this->info('Hak akses per peran diterapkan dari database/sql/roles.sql');
})->purpose('Terapkan GRANT/REVOKE akun database per peran');
