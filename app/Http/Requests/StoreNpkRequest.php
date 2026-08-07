<?php

namespace App\Http\Requests;

use App\Models\Npk;
use Illuminate\Foundation\Http\FormRequest;

class StoreNpkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Npk::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode'             => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', 'unique:npks,kode'],
            'kode_datapesanan' => 'nullable|string|max:100',
            'tanggal'          => 'required|date',
            'id_barang'        => 'required|exists:bahan,id',
            'id_gudang_asal'   => 'required|exists:gudangs,id',
            'id_gudang_tujuan' => 'nullable|exists:gudangs,id',
            'jumlah'           => 'required|numeric|gt:0',
            'close'            => 'required|in:0,1',
            'keterangan'       => 'nullable|string',
            'operator'         => 'nullable|string|max:100',
        ];
    }
}
