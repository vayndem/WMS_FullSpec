<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratJalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pesanan = $this->route('pesanan');

        return $this->user()?->can('kirim', $pesanan) ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'nomor_kendaraan' => ['nullable', 'string', 'max:30'],
            'pengirim' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.pesanan_penjualan_detail_id' => ['required', 'integer', 'exists:wms_pesanan_penjualan_detail,id'],
            'details.*.jumlah' => ['required', 'numeric', 'min:0'],
        ];
    }
}
