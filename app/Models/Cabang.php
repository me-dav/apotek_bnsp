<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    protected $table = 'cabang';

    protected $fillable = ['kode', 'nama', 'alamat', 'telepon'];

    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'cabang_id');
    }

    public function stok(): HasMany
    {
        return $this->hasMany(Stok::class, 'cabang_id');
    }

    // Satu cabang menyimpan banyak obat (lewat tabel stok)
    public function obat(): BelongsToMany
    {
        return $this->belongsToMany(Obat::class, 'stok', 'cabang_id', 'obat_id')
            ->withPivot('jumlah');
    }

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'cabang_id');
    }
}