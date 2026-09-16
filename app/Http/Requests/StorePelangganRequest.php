<?php

namespace App\Http\Requests;

use App\Models\Pelanggan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pelanggan = $this->route('pelanggan');

        return $pelanggan
            ? ($this->user()?->can('update', $pelanggan) ?? false)
            : ($this->user()?->can('create', Pelanggan::class) ?? false);
    }

    public function rules(): array
    {
        $id = $this->route('pelanggan')?->id;

        return [
            'kode' => ['required', 'string', 'max:30', Rule::unique('pelanggans', 'kode')->ignore($id)->whereNull('deleted_at')],
            'nama' => ['required', 'string', 'max:191'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'telp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'up' => ['nullable', 'string', 'max:100'],
            'termin_hari' => ['required', 'integer', 'min:0', 'max:365'],
            'plafon_kredit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
