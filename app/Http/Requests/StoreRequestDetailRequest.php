<?php

namespace App\Http\Requests;

use App\Models\RequestDetail;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequestDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RequestDetail::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'request_id'   => 'required|exists:requests,id',
            'bahan_id'     => 'nullable|exists:bahans,id',
            'nama_barang'  => 'required|string|max:255',
            'jumlah_minta' => 'required|numeric|gt:0',
            'keterangan'   => 'nullable|string',
            'kategori'     => 'nullable|string',
            'satuan'       => 'nullable|string',
            'berat_kecil'  => 'nullable|numeric|min:0',
            'satuan_kecil' => 'nullable|string',
            'tipe_gudang'  => 'nullable|string',
            'tipe_barang'  => 'nullable|string',
        ];
    }
}
