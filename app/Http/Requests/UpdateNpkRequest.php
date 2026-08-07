<?php

namespace App\Http\Requests;

use App\Models\Npk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNpkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $npkParam = $this->route('npk');
        $npk = $npkParam instanceof Npk ? $npkParam : Npk::find($npkParam);

        return $npk ? ($this->user()?->can('update', $npk) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'kode'             => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', Rule::unique('npks', 'kode')->ignore($this->route('npk'))],
            'kode_datapesanan' => 'nullable|string|max:100',
            'tanggal'          => 'required|date',
            'id_barang'        => 'required|exists:bahan,id',
            'id_gudang_asal'   => 'required|exists:gudangs,id',
            'id_gudang_tujuan' => 'nullable|exists:gudangs,id',
            'jumlah'           => 'required|numeric|gt:0',
            'close'            => 'required|in:0,1',
            'jumlah_terkirim'  => 'nullable|numeric|min:0',
            'tgl_terkirim'     => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'operator'         => 'nullable|string|max:100',
        ];
    }
}
