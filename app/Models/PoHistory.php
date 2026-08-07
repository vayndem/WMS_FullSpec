<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoHistory extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     * Wajib diisi karena nama model (PoHistory) tidak sesuai konvensi Laravel untuk tabel inv_po_history.
     */
    protected $table = 'inv_po_history';

    /**
     * Primary key dari tabel.
     * Wajib diisi karena bukan 'id'.
     */
    protected $primaryKey = 'id_history';

    /**
     * Menonaktifkan pengelolaan timestamp otomatis (created_at, updated_at) oleh Eloquent.
     * Kita menonaktifkannya karena tabel ini adalah log/arsip, dan timestamp-nya adalah salinan.
     */
    public $timestamps = false;

    /**
     * Definisikan relasi one-to-many ke PoDetailHistory.
     * Satu riwayat header memiliki banyak riwayat detail.
     */
    public function details()
    {
        // Dihubungkan oleh kolom 'no_revisi'
        return $this->hasMany(PoDetailHistory::class, 'no_revisi', 'no_revisi');
    }
    public function supplier()
    {
        // 'id_suplier' di POModel terhubung dengan 'id' di Supplier.
        return $this->belongsTo(Supplier::class, 'id_suplier', 'id');
    }
}
