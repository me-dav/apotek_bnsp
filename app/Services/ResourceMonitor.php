<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ResourceMonitor
{
    public function ambil(): array
    {
        $batas = config('monitoring');
        $db = $this->koneksiDb();

        return [
            'waktu' => now()->format('H:i:s'),
            'metrik' => [
                'cpu' => $this->metrik('CPU server', $this->cpuPersen(), '%', $batas['cpu_persen'], 100),
                'memori' => $this->metrik('Memori server', $this->memoriPersen(), '%', $batas['memori_persen'], 100),
                'koneksi' => $this->metrik(
                    'Koneksi database',
                    $db['aktif'],
                    'koneksi',
                    floor($db['maks'] * $batas['koneksi_persen'] / 100),
                    $db['maks']
                ),
                'disk' => $this->metrik('Disk terpakai', $this->diskPersen(), '%', $batas['disk_persen'], 100),
                'query' => $this->metrik(
                    'Waktu respon query',
                    $this->waktuQueryMs(),
                    'ms',
                    $batas['query_ms'],
                    $batas['query_ms'] * 1.5
                ),
            ],
        ];
    }

    private function metrik(string $label, ?float $nilai, string $satuan, float $batas, float $maks): array
    {
        $status = 'normal';

        if ($nilai === null) {
            $status = 'na';
        } elseif ($nilai >= $batas) {
            $status = 'kritis';
        } elseif ($nilai >= $batas * config('monitoring.rasio_peringatan')) {
            $status = 'peringatan';
        }

        return [
            'label' => $label,
            'nilai' => $nilai === null ? null : round($nilai, 1),
            'satuan' => $satuan,
            'batas' => round($batas, 1),
            'maks' => round($maks, 1),
            'status' => $status,
        ];
    }

    // CPU dan memori dibaca dari /proc, jadi hanya tersedia di Linux
    private function cpuPersen(): ?float
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        $beban = sys_getloadavg();
        if ($beban === false) {
            return null;
        }

        $core = max(1, substr_count((string) @file_get_contents('/proc/cpuinfo'), "processor\t"));

        return $beban[0] / $core * 100;
    }

    private function memoriPersen(): ?float
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        $info = (string) @file_get_contents('/proc/meminfo');
        if (
            !preg_match('/MemTotal:\s+(\d+)/', $info, $total)
            || !preg_match('/MemAvailable:\s+(\d+)/', $info, $tersedia)
        ) {
            return null;
        }

        return (1 - $tersedia[1] / $total[1]) * 100;
    }

    private function diskPersen(): ?float
    {
        $total = @disk_total_space(base_path());
        $bebas = @disk_free_space(base_path());

        if (!$total || $bebas === false) {
            return null;
        }

        return (1 - $bebas / $total) * 100;
    }

    private function koneksiDb(): array
    {
        return [
            'aktif' => (int) DB::scalar('SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()'),
            'maks' => (int) DB::scalar("SELECT current_setting('max_connections')::int"),
        ];
    }

    // Waktu respon diukur dari satu kueri uji yang representatif
    private function waktuQueryMs(): float
    {
        $mulai = hrtime(true);
        DB::select('SELECT * FROM v_stok_kritis');

        return (hrtime(true) - $mulai) / 1_000_000;
    }
}