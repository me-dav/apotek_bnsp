<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class PesanDb
{
    /**
     * Ambil pesan RAISE EXCEPTION dari PostgreSQL (SQLSTATE P0001) agar aman ditampilkan.
     * Error lain dicatat ke log dan diganti pesan umum supaya detail database tidak bocor.
     */
    public static function dari(QueryException $e): string
    {
        if (
            $e->getCode() === 'P0001'
            && preg_match('/ERROR:\s+(.+?)(\n|\(Connection|$)/s', $e->getMessage(), $m)
        ) {
            return trim($m[1]);
        }

        report($e);

        return 'Proses gagal. Silakan coba lagi.';
    }
}