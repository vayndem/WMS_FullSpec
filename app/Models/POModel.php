<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POModel extends Model
{
    use HasFactory;

    protected $table = 'inv_po';
    protected $primaryKey = 'no_po';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;



    private static function convertToRoman($month)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $map[$month];
    }
    public static function generatePONumber($tanggal, $jenis)
    {
        if ($jenis == 0) {
            $suffiks = '-PO-';
        } else  if ($jenis == 1) {
            $suffiks = '-PP-';
        } else {
            $suffiks = '-MO-';
        }
        // Mendapatkan bulan dan tahun saat ini
        $currentMonth = date('n', strtotime($tanggal));  // Bulan dalam angka
        $currentYear = date('Y', strtotime($tanggal));
        $lastPO = self::select('no_po')->where('jenis', $jenis) // Pastikan untuk memilih no_po
            ->whereYear('tanggal', $currentYear)
            ->whereMonth('tanggal', $currentMonth)
            ->orderBy('id', 'desc') // Mengurutkan berdasarkan nomor PO terakhir
            ->first();

        // Mendapatkan nomor urut terakhir
        $lastNumber = 0;
        if ($lastPO) {
            // Ambil angka sebelum tanda '-'
            $lastNumber = intval(substr($lastPO->no_po, 0, strpos($lastPO->no_po, '-')));
        }

        // Nomor urut baru (ubah padding dari 3 digit menjadi 2 digit)
        $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
        // Mengonversi bulan ke angka Romawi
        $romanMonth = self::convertToRoman($currentMonth);
        $poNumber = $newNumber . $suffiks . $romanMonth . '-' . $currentYear;
        return $poNumber;
    }
    public static function headernomorpo($nomorpo)
    {
        $header = self::where('no_po', $nomorpo)->first();

        // Jika PO tidak ditemukan sama sekali, kembalikan null
        if (!$header) {
            return null;
        }

        // --- VALIDASI BARU DITAMBAHKAN DI SINI ---
        // Jika PO sudah terkunci, lemparkan Exception dengan pesan error
        if ($header->kunci == 1) {
            throw new \Exception('Purchase Order ini sudah dikunci dan tidak bisa diproses lagi.');
        }


        // Jika tidak terkunci, lanjutkan proses update seperti biasa
        if ($header->cetak_ulang == '0') {
            $header->kunci = 1;
            $header->cetak = $header->cetak + 1;
            $header->save();
        } else {
            $header->kunci = 1;
            $header->cetak =  1;
            $header->counter_asli = $header->counter_asli + 1;
            $header->cetak_ulang = 0;
            $header->save();
        }
        $header_with_join = self::where('no_po', $nomorpo)
            ->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->select(
                'inv_po.*',
                'suppliers.*'
            )
            ->first();
        return $header_with_join;
    }
    public function details()
    {
        // 'no_po' di DetailPOModel terhubung dengan 'no_po' di POModel.
        return $this->hasMany(DetailPOModel::class, 'no_po', 'no_po');
    }

    /**
     * Definisikan relasi ke model Supplier.
     * Asumsi nama modelnya adalah Supplier. Sesuaikan jika berbeda.
     */
    public function supplier()
    {
        // 'id_suplier' di POModel terhubung dengan 'id' di Supplier.
        return $this->belongsTo(Supplier::class, 'id_suplier', 'id');
    }
}
