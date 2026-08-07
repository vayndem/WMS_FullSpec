<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AccountingSetting extends Model
{
    public const HUTANG_USAHA = 'HUTANG_USAHA';
    public const PPN_MASUKAN = 'PPN_MASUKAN';
    public const HUTANG_PPH23 = 'HUTANG_PPH23';
    public const BIAYA_BANK = 'BIAYA_BANK';
    public const BEBAN_MATERAI = 'BEBAN_MATERAI';
    public const SELISIH_BAYAR = 'SELISIH_BAYAR';
    public const BIAYA_ONGKIR = 'BIAYA_ONGKIR';
    public const DISKON_PEMBELIAN = 'DISKON_PEMBELIAN';

    protected $fillable = ['key', 'coa_id', 'description'];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public static function accountId(string $key): int
    {
        $setting = static::with('coa')->where('key', $key)->first();
        $expected = [
            self::HUTANG_USAHA => [['LIABILITAS', 'KREDIT']],
            self::PPN_MASUKAN => [['ASET', 'DEBIT']],
            self::HUTANG_PPH23 => [['LIABILITAS', 'KREDIT']],
            self::BIAYA_BANK => [['BEBAN', 'DEBIT']],
            self::BEBAN_MATERAI => [['BEBAN', 'DEBIT']],
            self::SELISIH_BAYAR => [['PENDAPATAN', 'KREDIT']],
            self::BIAYA_ONGKIR => [['BEBAN', 'DEBIT']],
            self::DISKON_PEMBELIAN => [['BEBAN', 'KREDIT']],
        ];
        if (
            !$setting
            || !$setting->coa
            || !isset($expected[$key])
            || !$setting->coa->isUsableFor($expected[$key])
        ) {
            throw new RuntimeException("Mapping akun {$key} belum diatur atau akun tidak aktif.");
        }

        return (int) $setting->coa_id;
    }
}
