<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoDetailHistory extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 'inv_podetail_history'; // Disesuaikan dari contoh inv_pokertasdetail_history

    /**
     * Primary key dari tabel.
     */
    protected $primaryKey = 'id_history_detail';

    /**
     * Menonaktifkan pengelolaan timestamp otomatis oleh Eloquent.
     */
    public $timestamps = false;

    /**
     * Definisikan relasi "belongsTo" ke PoHistory.
     * Satu riwayat detail dimiliki oleh satu riwayat header.
     */
    public function header()
    {
        // Dihubungkan oleh kolom 'no_revisi'
        return $this->belongsTo(PoHistory::class, 'no_revisi', 'no_revisi');
    }
    public function bahan()
    {
        // 'id_bahan' di tabel histori detail ini terhubung dengan 'id' di tabel bahan.
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id');
    }
}
