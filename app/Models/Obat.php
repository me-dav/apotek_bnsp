<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    public const KATEGORI_OBAT_KERAS = 'OK';

    protected $table = 'obat';

    protected $fillable = ['kode', 'nama', 'kategori_id', 'satuan', 'harga_jual', 'stok_minimum'];

    protected function casts(): array
    {
        return ['harga_jual' => 'decimal:2'];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriObat::class, 'kategori_id');
    }

    public function stok(): HasMany
    {
        return $this->hasMany(Stok::class, 'obat_id');
    }

    // Satu obat tersedia di banyak cabang (lewat tabel stok)
    public function cabang(): BelongsToMany
    {
        return $this->belongsToMany(Cabang::class, 'stok', 'obat_id', 'cabang_id')
            ->withPivot('jumlah');
    }

    public function isObatKeras(): bool
    {
        return $this->kategori?->kode === self::KATEGORI_OBAT_KERAS;
    }
}