<?php

namespace App\Http\Requests;

use App\Models\Lpb;
use Illuminate\Foundation\Http\FormRequest;

class StoreLpbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lpb::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'id_lpb'                           => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', 'unique:lpbs,id_lpb'],
            'tanggal'                          => 'required|date',
            'no_po'                            => 'required|string|exists:pembelians,no_po',
            'no_sj'                            => 'required|string|max:250',
            'no_invoice'                       => 'nullable|string|max:50',
            'jenis_lpb'                        => 'nullable|integer',
            'details'                          => 'required|array|min:1',
            'details.*.id_bahan'               => 'required|integer|exists:bahan,id',
            'details.*.id_kategori'            => 'required|integer|exists:kategoribahan,id',
            'details.*.jumlah_barang_diterima' => 'required|numeric|gt:0',
            'details.*.lot_number'             => 'nullable|string|max:80',
            'confirm_over_receive'             => 'nullable|boolean',
        ];
    }
}
