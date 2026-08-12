<?php

namespace App\Http\Requests;

use App\Models\RequestDetail;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $detail = $this->route('requestdetail');
        $model = $detail instanceof RequestDetail ? $detail : RequestDetail::find($detail);

        return $model ? ($this->user()?->can('update', $model) ?? false) : false;
    }

    public function rules(): array
    {
        return [
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
