<?php

namespace App\Http\Requests;

use App\Models\KategoriBahan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKategoriBahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kategori = $this->route('kategori_bahan') ?? $this->route('id');
        if (is_numeric($kategori)) {
            $kategori = KategoriBahan::find($kategori);
        }

        return $kategori ? ($this->user()?->can('update', $kategori) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'katnama'             => 'required|string|max:255',
            'tipe_pembebanan_id' => 'nullable|integer|exists:tipe_pembebanans,id',
            'coa_persediaan_id'   => 'required|integer|exists:chart_of_accounts,id',
            'coa_beban_id'        => 'required|integer|exists:chart_of_accounts,id',
            'coa_clearing_lpb_id' => 'required|integer|exists:chart_of_accounts,id',
            'coa_beban_selisih_opname_id' => 'required|integer|exists:chart_of_accounts,id',
            'coa_koreksi_opname_id' => 'required|integer|exists:chart_of_accounts,id',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach (['coa_persediaan_id' => ['ASET', 'DEBIT'], 'coa_beban_id' => ['BEBAN', 'DEBIT'],
                'coa_clearing_lpb_id' => ['LIABILITAS', 'KREDIT'],
                'coa_beban_selisih_opname_id' => ['BEBAN', 'DEBIT'],
                'coa_koreksi_opname_id' => ['PENDAPATAN', 'KREDIT']] as $field => $expected) {
                $coa = \App\Models\ChartOfAccount::find($this->input($field));
                if ($coa && (!$coa->is_active || !$coa->is_postable || $coa->kategori_akun !== $expected[0] || $coa->posisi_normal !== $expected[1])) {
                    $validator->errors()->add($field, 'Akun tidak aktif atau kategori/posisi normalnya tidak sesuai.');
                }
            }
        }];
    }
}
