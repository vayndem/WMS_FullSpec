<?php

namespace App\Http\Requests;

use App\Models\PengeluaranBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengeluaranBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') === true
            || $this->user()?->can('viewWmsControl') === true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'pesanan_jasa_id' => 'nullable|integer|exists:wms_pesanan_pembelian,id',
            'keperluan' => ['required', Rule::in(array_keys(PengeluaranBarang::KEPERLUAN))],
            'estimasi_kembali' => 'nullable|date|after_or_equal:tanggal',
            'catatan' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.aset_id' => 'nullable|integer|exists:wms_asets,id',
            'items.*.deskripsi' => 'required|string|max:255',
            'items.*.nomor_seri' => 'nullable|string|max:100',
            'items.*.jumlah' => 'nullable|numeric|gt:0',
            'items.*.satuan' => 'nullable|string|max:30',
            'items.*.kondisi_keluar' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return ['items' => 'daftar barang', 'estimasi_kembali' => 'estimasi kembali'];
    }
}
