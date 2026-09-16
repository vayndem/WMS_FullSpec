<?php

namespace App\Http\Requests;

use App\Models\BaganAkun;
use App\Models\PelepasanAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelepasanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispose', $this->route('aset'));
    }

    public function rules(): array
    {
        $jenis = $this->input('disposal_type');

        return [
            'disposal_date' => 'required|date',
            'disposal_type' => ['required', Rule::in(array_keys(PelepasanAset::JENIS))],
            'proceeds' => [
                'nullable',
                Rule::requiredIf(in_array($jenis, [PelepasanAset::SALE, PelepasanAset::TRADE_IN], true)),
                'numeric', 'min:0',
            ],
            'cash_bank_coa_id' => ['nullable', Rule::requiredIf($jenis === PelepasanAset::SALE), 'exists:wms_bagan_akun,id'],
            'supplier_id' => ['nullable', Rule::requiredIf($jenis === PelepasanAset::TRADE_IN), 'exists:suppliers,id'],
            'pesanan_pembelian_id' => ['nullable', 'exists:wms_pesanan_pembelian,id'],
            'ppn_keluaran' => ['nullable', 'numeric', 'min:0'],
            'reason' => 'required|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier penerima tukar tambah',
            'ppn_keluaran' => 'PPN Keluaran',
            'proceeds' => 'nilai wajar / hasil pelepasan',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $jenis = $this->input('disposal_type');

            if ($jenis === PelepasanAset::SALE) {
                $account = BaganAkun::find($this->input('cash_bank_coa_id'));
                if ($account && !$account->isUsableFor([['ASET', 'DEBIT']], true)) {
                    $validator->errors()->add('cash_bank_coa_id', 'Hasil penjualan wajib masuk akun Kas/Bank yang aktif dan postable.');
                }
            }

            if ($jenis === PelepasanAset::TRADE_IN && (float) $this->input('proceeds', 0) <= 0) {
                $validator->errors()->add('proceeds',
                    'Tukar tambah wajib mencantumkan nilai wajar barang yang diserahkan — itulah DPP PPN Keluaran-nya.');
            }
        }];
    }
}
