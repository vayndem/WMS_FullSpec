<?php

namespace App\Http\Requests;

use App\Models\DataPesanan;
use Illuminate\Foundation\Http\FormRequest;

class StoreDataPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DataPesanan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'pesanan_penjualan_detail_id' => ['required', 'integer', 'exists:wms_pesanan_penjualan_detail,id'],
            'tanggal' => ['required', 'date'],
            'gudang_id' => ['nullable', 'integer', 'exists:gudangs,id'],
            'jumlah_rencana' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pesanan_penjualan_detail_id' => 'baris pesanan penjualan',
            'jumlah_rencana' => 'jumlah rencana produksi',
        ];
    }
}
