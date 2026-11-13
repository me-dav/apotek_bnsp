<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\KategoriObat;
use App\Models\Obat;
use App\Models\Pengguna;
use App\Models\Stok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cabang = $this->seedCabang();
        $kategori = $this->seedKategori();
        $obat = $this->seedObat($kategori);
        $this->seedStok($cabang, $obat);
        $pengguna = $this->seedPengguna($cabang);
        $this->seedPenjualan($cabang, $obat, $pengguna);
    }

    private function seedCabang()
    {
        $data = [
            ['CBG-001', 'Apotek Sehat Jakarta Pusat', 'Jl. Kramat Raya No. 10, Jakarta Pusat', '021-1111001'],
            ['CBG-002', 'Apotek Sehat Depok', 'Jl. Margonda Raya No. 25, Depok', '021-1111002'],
            ['CBG-003', 'Apotek Sehat Bekasi', 'Jl. Ahmad Yani No. 7, Bekasi', '021-1111003'],
        ];

        return collect($data)->mapWithKeys(fn($d) => [
            $d[0] => Cabang::create([
                'kode' => $d[0],
                'nama' => $d[1],
                'alamat' => $d[2],
                'telepon' => $d[3],
            ]),
        ]);
    }

    private function seedKategori()
    {
        $data = [
            ['OB', 'Obat Bebas'],
            ['OBT', 'Obat Bebas Terbatas'],
            ['OK', 'Obat Keras'],
            ['ALK', 'Alat Kesehatan'],
        ];

        return collect($data)->mapWithKeys(fn($d) => [
            $d[0] => KategoriObat::create(['kode' => $d[0], 'nama' => $d[1]]),
        ]);
    }

    private function seedObat($kategori)
    {
        // kode, nama, kategori, satuan, harga jual, stok minimum
        $data = [
            ['OBT-001', 'Paracetamol 500 mg', 'OB', 'Strip', 5000, 20],
            ['OBT-002', 'Vitamin C 500 mg', 'OB', 'Botol', 25000, 10],
            ['OBT-003', 'Antasida Tablet', 'OB', 'Strip', 8000, 15],
            ['OBT-004', 'CTM 4 mg', 'OBT', 'Strip', 3000, 15],
            ['OBT-005', 'Obat Batuk Sirup', 'OBT', 'Botol', 18000, 10],
            ['OBT-006', 'Amoxicillin 500 mg', 'OK', 'Strip', 22000, 10],
            ['OBT-007', 'Ciprofloxacin 500 mg', 'OK', 'Strip', 30000, 10],
            ['OBT-008', 'Metformin 500 mg', 'OK', 'Strip', 12000, 10],
            ['OBT-009', 'Termometer Digital', 'ALK', 'Pcs', 45000, 5],
            ['OBT-010', 'Masker Medis', 'ALK', 'Box', 35000, 10],
        ];

        return collect($data)->mapWithKeys(fn($d) => [
            $d[0] => Obat::create([
                'kode' => $d[0],
                'nama' => $d[1],
                'kategori_id' => $kategori[$d[2]]->id,
                'satuan' => $d[3],
                'harga_jual' => $d[4],
                'stok_minimum' => $d[5],
            ]),
        ]);
    }

    private function seedStok($cabang, $obat): void
    {
        foreach ($cabang as $c) {
            foreach ($obat as $o) {
                $jumlah = 50 + $o->id * 5;

                // Sengaja dibuat kritis agar muncul di view stok kritis
                if ($c->kode === 'CBG-002' && $o->kode === 'OBT-006') {
                    $jumlah = 5;
                }

                Stok::create(['cabang_id' => $c->id, 'obat_id' => $o->id, 'jumlah' => $jumlah]);

                // Pecah menjadi dua batch: yang lama cepat kedaluwarsa, yang baru masih lama
                $lama = intdiv($jumlah, 2);
                $this->buatBatch($c->id, $o->id, 'B-LAMA', now()->addDays(60)->toDateString(), $lama);
                $this->buatBatch($c->id, $o->id, 'B-BARU', now()->addDays(400)->toDateString(), $jumlah - $lama);
            }
        }
    }

    private function buatBatch(int $cabangId, int $obatId, string $noBatch, string $kedaluwarsa, int $jumlah): void
    {
        DB::table('batch_stok')->insert([
            'cabang_id' => $cabangId,
            'obat_id' => $obatId,
            'no_batch' => $noBatch,
            'tanggal_kedaluwarsa' => $kedaluwarsa,
            'jumlah' => $jumlah,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPengguna($cabang)
    {
        $data = [
            ['admin', 'Admin Pusat', Pengguna::ADMIN_PUSAT, null],
            ['apoteker1', 'Apoteker Jakarta', Pengguna::APOTEKER, 'CBG-001'],
            ['kasir1', 'Kasir Jakarta', Pengguna::KASIR, 'CBG-001'],
            ['kasir2', 'Kasir Depok', Pengguna::KASIR, 'CBG-002'],
        ];

        return collect($data)->mapWithKeys(fn($d) => [
            $d[0] => Pengguna::create([
                'username' => $d[0],
                'password' => 'password', // otomatis di-hash oleh model
                'nama_lengkap' => $d[1],
                'role' => $d[2],
                'cabang_id' => $d[3] ? $cabang[$d[3]]->id : null,
            ]),
        ]);
    }

    private function seedPenjualan($cabang, $obat, $pengguna): void
    {
        // Memakai stored procedure, jadi stok ikut berkurang lewat trigger
        $this->jual(
            'FKT-SEED-001',
            $cabang['CBG-001'],
            $pengguna['kasir1'],
            ['OBT-001' => 2, 'OBT-002' => 1],
            '2026-11-10 09:15:00',
            $obat
        );
        $this->jual(
            'FKT-SEED-002',
            $cabang['CBG-001'],
            $pengguna['kasir1'],
            ['OBT-004' => 3],
            '2026-11-10 17:40:00',
            $obat
        );
        $this->jual(
            'FKT-SEED-003',
            $cabang['CBG-001'],
            $pengguna['apoteker1'],
            ['OBT-006' => 2, 'OBT-001' => 1],
            '2026-11-11 10:05:00',
            $obat
        );
        $this->jual(
            'FKT-SEED-004',
            $cabang['CBG-002'],
            $pengguna['kasir2'],
            ['OBT-003' => 2, 'OBT-010' => 5],
            '2026-11-12 18:20:00',
            $obat
        );
        $this->jual(
            'FKT-SEED-005',
            $cabang['CBG-001'],
            $pengguna['apoteker1'],
            ['OBT-008' => 3],
            '2026-11-12 19:10:00',
            $obat
        );
    }

    private function jual(string $faktur, $cabang, $kasir, array $barang, string $tanggal, $obat): void
    {
        $items = [];
        foreach ($barang as $kode => $jumlah) {
            $items[] = ['obat_id' => $obat[$kode]->id, 'jumlah' => $jumlah];
        }

        DB::statement('CALL simpan_penjualan(?, ?, ?, CAST(? AS jsonb))', [
            $faktur,
            $cabang->id,
            $kasir->id,
            json_encode($items),
        ]);

        DB::table('penjualan')->where('no_faktur', $faktur)->update(['tanggal' => $tanggal]);
    }
}