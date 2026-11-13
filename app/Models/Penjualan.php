<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    protected $table = 'penjualan';

    public $timestamps = false;

    protected $fillable = ['no_faktur', 'tanggal', 'cabang_id', 'pengguna_id', 'total'];

    protected function casts(): array
    {
        return ['tanggal' => 'datetime', 'total' => 'decimal:2'];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    // Hanya ambil kolom aman (tanpa password) agar cocok dengan hak akses akun DB
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id')->select('id', 'nama_lengkap');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }
}