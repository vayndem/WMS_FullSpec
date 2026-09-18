<?php

namespace App\Http\Requests;

use App\Models\ReturPembelian;
use Illuminate\Foundation\Http\FormRequest;

class StoreReturPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReturPembelian::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'lpb_id'                       => ['required', 'integer', 'exists:wms_penerimaan_barang,id'],
            'tanggal'                      => ['required', 'date'],
            'alasan'                       => ['required', 'string', 'max:1000'],
            'details'                      => ['required', 'array', 'min:1'],
            'details.*.lpb_detail_id'      => ['required', 'integer', 'distinct', 'exists:wms_penerimaan_barang_detail,id'],
            'details.*.jumlah_retur'       => ['required', 'numeric', 'min:0.000001'],
        ];
    }
}
