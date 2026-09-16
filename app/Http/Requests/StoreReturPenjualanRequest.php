<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturPenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('retur', $this->route('suratJalan')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.surat_jalan_detail_id' => ['required', 'integer', 'exists:wms_surat_jalan_detail,id'],
            'details.*.jumlah' => ['required', 'numeric', 'min:0'],
        ];
    }
}
