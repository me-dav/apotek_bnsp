<?php

namespace App\Support;

/**
 * Kumpulan algoritma pencarian dan pengurutan.
 * Fungsi menerima objek yang punya properti kode/nama/terjual
 * (model Eloquent atau stdClass hasil query), jadi tidak terikat model tertentu.
 */
class Algoritma
{
    /**
     * Pencarian LINEAR: nama atau kode mengandung kata kunci (tanpa membedakan huruf besar/kecil).
     *
     * Waktu  : O(n * m)  n = jumlah obat, m = panjang kata kunci
     * Memori : O(h)      h = jumlah hasil yang cocok
     */
    public static function cariObat(iterable $daftar, string $kataKunci): array
    {
        $kata = mb_strtolower(trim($kataKunci));
        $hasil = [];

        foreach ($daftar as $obat) {
            if (
                str_contains(mb_strtolower($obat->nama), $kata)
                || str_contains(mb_strtolower($obat->kode), $kata)
            ) {
                $hasil[] = $obat;
            }
        }

        return $hasil;
    }

    /**
     * Pencarian BINER berdasarkan kode persis.
     * Syarat: $daftar sudah terurut menaik menurut kode (urutan byte, sama dengan strcmp).
     *
     * Waktu  : O(log n)
     * Memori : O(1)  (iteratif, tanpa rekursi)
     */
    public static function cariBinerKode(array $daftar, string $kode): ?object
    {
        $kiri = 0;
        $kanan = count($daftar) - 1;

        while ($kiri <= $kanan) {
            $tengah = intdiv($kiri + $kanan, 2);
            $banding = strcmp($daftar[$tengah]->kode, $kode);

            if ($banding === 0) {
                return $daftar[$tengah];
            }

            if ($banding < 0) {
                $kiri = $tengah + 1;
            } else {
                $kanan = $tengah - 1;
            }
        }

        return null;
    }

    /**
     * Urutkan obat terlaris (jumlah terjual terbanyak dulu) dengan MERGE SORT.
     * Jika jumlah sama, urut nama A-Z. Stabil.
     *
     * Waktu  : O(n log n) pada semua kasus
     * Memori : O(n)
     */
    public static function urutkanTerlaris(array $data): array
    {
        $n = count($data);
        if ($n <= 1) {
            return $data;
        }

        $tengah = intdiv($n, 2);
        $kiri = self::urutkanTerlaris(array_slice($data, 0, $tengah));
        $kanan = self::urutkanTerlaris(array_slice($data, $tengah));

        return self::gabung($kiri, $kanan);
    }

    private static function gabung(array $kiri, array $kanan): array
    {
        $hasil = [];
        $i = 0;
        $j = 0;
        $nKiri = count($kiri);
        $nKanan = count($kanan);

        while ($i < $nKiri && $j < $nKanan) {
            if (self::lebihDulu($kiri[$i], $kanan[$j])) {
                $hasil[] = $kiri[$i++];
            } else {
                $hasil[] = $kanan[$j++];
            }
        }

        while ($i < $nKiri) {
            $hasil[] = $kiri[$i++];
        }
        while ($j < $nKanan) {
            $hasil[] = $kanan[$j++];
        }

        return $hasil;
    }

    // true bila $a harus tampil sebelum $b (seri: ambil kiri agar stabil)
    private static function lebihDulu(object $a, object $b): bool
    {
        $x = (int) $a->terjual;
        $y = (int) $b->terjual;

        if ($x !== $y) {
            return $x > $y;
        }

        return strcmp($a->nama, $b->nama) <= 0;
    }
}