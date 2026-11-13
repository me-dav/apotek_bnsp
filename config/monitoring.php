<?php

// Batas atas tiap resource. Melewati batas ini aplikasi berisiko lambat atau gagal.
return [
    'cpu_persen' => (float) env('MONITOR_BATAS_CPU', 85),
    'memori_persen' => (float) env('MONITOR_BATAS_MEMORI', 85),
    'koneksi_persen' => (float) env('MONITOR_BATAS_KONEKSI', 80),
    'disk_persen' => (float) env('MONITOR_BATAS_DISK', 85),
    'query_ms' => (float) env('MONITOR_BATAS_QUERY_MS', 2000),

    // Status "peringatan" mulai dari 80% batas atas
    'rasio_peringatan' => 0.8,
];