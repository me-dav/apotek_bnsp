-- Dataset uji kinerja. Jalankan sebagai owner pada database hasil migrate:fresh --seed.
\timing on
BEGIN;

-- Trigger stok dimatikan sementara: ini data uji, bukan transaksi sungguhan
ALTER TABLE detail_penjualan DISABLE TRIGGER trg_detail_penjualan_kurangi_stok;

-- 1) Obat sampai total 2.500 jenis
INSERT INTO obat (kode, nama, kategori_id, satuan, harga_jual, stok_minimum, created_at, updated_at)
SELECT 'GEN-' || lpad(g::text, 5, '0'),
       'Obat Generik ' || g,
       (SELECT id FROM kategori_obat ORDER BY id LIMIT 1 OFFSET (g % 4)),
       'Strip', 1000 + (g % 50) * 500, 10, now(), now()
  FROM generate_series(1, 2500 - (SELECT count(*) FROM obat)) AS g;

-- 2) Stok dan batch untuk obat baru di setiap cabang
INSERT INTO stok (cabang_id, obat_id, jumlah, created_at, updated_at)
SELECT c.id, o.id, 500, now(), now()
  FROM cabang c CROSS JOIN obat o
 WHERE NOT EXISTS (SELECT 1 FROM stok s WHERE s.cabang_id = c.id AND s.obat_id = o.id);

INSERT INTO batch_stok (cabang_id, obat_id, no_batch, tanggal_kedaluwarsa, jumlah, created_at, updated_at)
SELECT s.cabang_id, s.obat_id, 'B-PERF', DATE '2030-12-31', s.jumlah, now(), now()
  FROM stok s
 WHERE NOT EXISTS (SELECT 1 FROM batch_stok b WHERE b.cabang_id = s.cabang_id AND b.obat_id = s.obat_id);

-- 3) Faktur: 1.200 per hari sepanjang 2026, jam 08.00-21.00 (seed memiliki 3 cabang)
INSERT INTO penjualan (no_faktur, tanggal, cabang_id, pengguna_id, total)
SELECT 'PERF-' || lpad(g::text, 7, '0'),
       DATE '2026-01-01' + ((g - 1) / 1200)
         + make_interval(secs => 8 * 3600 + (g * 37) % (13 * 3600)),
       (ARRAY(SELECT id FROM cabang ORDER BY id))[1 + g % 3],
       (SELECT id FROM pengguna WHERE username = 'kasir1'),
       0
  FROM generate_series(1, 438000) AS g;

-- 4) Detail: 1-3 item per faktur, obat dipilih pseudo-acak tanpa duplikat dalam satu faktur
WITH urut AS (
    SELECT id, harga_jual, row_number() OVER (ORDER BY id) - 1 AS rn FROM obat
)
INSERT INTO detail_penjualan (penjualan_id, obat_id, jumlah, harga_satuan)
SELECT p.id, o.id, 1 + (p.id + k) % 5, o.harga_jual
  FROM penjualan p
  JOIN generate_series(1, 3) AS k ON k <= 1 + (p.id % 3)
  JOIN urut o ON o.rn = (p.id * 7919 + k * 104729) % 2500
 WHERE p.no_faktur LIKE 'PERF-%';

-- 5) Total faktur
UPDATE penjualan p
   SET total = t.total
  FROM (SELECT penjualan_id, SUM(subtotal) AS total FROM detail_penjualan GROUP BY penjualan_id) t
 WHERE t.penjualan_id = p.id AND p.no_faktur LIKE 'PERF-%';

ALTER TABLE detail_penjualan ENABLE TRIGGER trg_detail_penjualan_kurangi_stok;
COMMIT;

ANALYZE;

SELECT (SELECT count(*) FROM obat)             AS obat,
       (SELECT count(*) FROM penjualan)        AS penjualan,
       (SELECT count(*) FROM detail_penjualan) AS detail;