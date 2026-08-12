<?php

namespace App\Http\Requests;

use App\Models\Lpb;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLpbDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lpb::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'id_kategori'            => 'nullable|integer|exists:kategori_bahans,id',
            'jumlah_barang_diterima' => 'required|numeric|gt:0',
            'lot_number'             => 'nullable|string|max:80',
            'harga'                  => 'nullable|integer',
        ];
    }
}
