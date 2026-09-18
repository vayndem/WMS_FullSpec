<?php

namespace App\Http\Requests;

use App\Models\Bom;
use Illuminate\Foundation\Http\FormRequest;

class StoreOperasiBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bom = $this->route('bom');

        return $bom instanceof Bom && ($this->user()?->can('update', $bom) ?? false);
    }

    public function rules(): array
    {
        return [
            'operasi' => ['required', 'array', 'min:1'],
            'operasi.*.pusat_kerja_id' => ['required', 'integer', 'exists:wms_pusat_kerja,id'],
            'operasi.*.urutan' => ['required', 'integer', 'min:1', 'max:999'],
            'operasi.*.nama_operasi' => ['required', 'string', 'max:191'],
            'operasi.*.waktu_standar_menit' => ['nullable', 'numeric', 'min:0'],
            'operasi.*.catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
