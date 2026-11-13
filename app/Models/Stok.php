<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HanyaCabangSendiri;

class Stok extends Model
{
    use HanyaCabangSendiri;
    protected $table = 'stok';

    protected $fillable = ['cabang_id', 'obat_id', 'jumlah'];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}