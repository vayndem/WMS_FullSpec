<?php

namespace App\Http\Requests;

use App\Models\CrossDock;
use Illuminate\Foundation\Http\FormRequest;

class StoreCrossDockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CrossDock::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'penerimaan_barang_detail_id' => ['required', 'integer', 'exists:wms_penerimaan_barang_detail,id'],
            'pesanan_penjualan_detail_id' => ['required', 'integer', 'exists:wms_pesanan_penjualan_detail,id'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
