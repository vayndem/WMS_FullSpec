<?php

namespace App\Http\Requests;

use App\Models\Lpb;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLpbRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lpb = $this->route('lpb');

        if (is_string($lpb)) {
            $lpb = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)->first();
        }

        return $lpb ? ($this->user()?->can('update', $lpb) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'tanggal'                          => 'required|date',
            'no_sj'                            => 'required|string|max:250',
            'no_invoice'                       => 'nullable|string|max:50',
            'jenis_lpb'                        => 'nullable|integer',
            'details'                          => 'required|array|min:1',
            'details.*.id'                     => 'nullable|integer|exists:lpb_details,id',
            'details.*.id_bahan'               => 'required|integer|exists:bahans,id',
            'details.*.id_kategori'            => 'required|integer|exists:kategori_bahans,id',
            'details.*.jumlah_barang_diterima' => 'required|numeric|gt:0',
            'details.*.lot_number'             => 'nullable|string|max:80',
            'details.*.harga'                  => 'nullable|integer',
            'confirm_over_receive'             => 'nullable|boolean',
        ];
    }
}
