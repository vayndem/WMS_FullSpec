<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangTitipanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') === true
            || $this->user()?->can('viewWmsControl') === true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'pengeluaran_id' => 'nullable|integer|exists:wms_pengeluaran_barang,id',
            'deskripsi' => 'required|string|max:255',
            'nomor_seri' => 'nullable|string|max:100',
            'jumlah' => 'nullable|numeric|gt:0',
            'satuan' => 'nullable|string|max:30',
            'tanggal_terima' => 'required|date',
            'estimasi_kembali' => 'nullable|date|after_or_equal:tanggal_terima',
            'nilai_taksiran' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return ['nilai_taksiran' => 'nilai taksiran (memo)'];
    }
}
