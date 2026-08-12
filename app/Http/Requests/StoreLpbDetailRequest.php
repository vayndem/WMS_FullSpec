<?php

namespace App\Http\Requests;

use App\Models\Lpb;
use Illuminate\Foundation\Http\FormRequest;

class StoreLpbDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lpb::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'id_bahan'               => 'required|integer|exists:bahans,id',
            'id_kategori'            => 'required|integer|exists:kategori_bahans,id',
            'jumlah_barang_diterima' => 'required|numeric|gt:0',
            'lot_number'             => 'nullable|string|max:80',
            'harga'                  => 'required|numeric|gt:0',
        ];
    }
}
