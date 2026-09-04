<?php

namespace App\Http\Requests;

use App\Models\PemakaianBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePemakaianBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $npkParam = $this->route('npk');
        $npk = $npkParam instanceof PemakaianBarang ? $npkParam : PemakaianBarang::find($npkParam);

        return $npk ? ($this->user()?->can('update', $npk) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'kode'             => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', Rule::unique('wms_pemakaian_barang', 'kode')->ignore($this->route('npk'))],
            'kode_datapesanan' => 'nullable|string|max:100',
            'tanggal'          => 'required|date',
            'id_barang'        => 'required|exists:bahans,id',
            'id_gudang_asal'   => 'required|exists:gudangs,id',
            'id_gudang_tujuan' => 'nullable|exists:gudangs,id',
            'inventory_reservation_id' => 'nullable|exists:wms_reservasi_persediaan,id',
            'jumlah'           => 'required|numeric|gt:0',
            'status'           => 'required|in:DRAFT,POSTED',
            'jumlah_terkirim'  => 'nullable|numeric|min:0',
            'tgl_terkirim'     => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'operator'         => 'nullable|string|max:100',
        ];
    }
}
