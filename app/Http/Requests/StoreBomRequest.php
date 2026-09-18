<?php

namespace App\Http\Requests;

use App\Models\Bom;
use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Bom::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:30', 'unique:wms_bom,kode'],
            'nama' => ['required', 'string', 'max:191'],
            'bahan_id' => ['required', 'integer', 'exists:bahans,id'],
            'versi' => ['nullable', 'string', 'max:20'],
            'jumlah_hasil' => ['nullable', 'numeric', 'gt:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.bahan_id' => ['required', 'integer', 'exists:bahans,id'],
            'details.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'details.*.satuan' => ['nullable', 'string', 'max:30'],
            'details.*.catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
