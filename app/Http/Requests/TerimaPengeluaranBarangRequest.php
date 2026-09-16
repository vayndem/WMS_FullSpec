<?php

namespace App\Http\Requests;

use App\Models\PengeluaranBarangDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerimaPengeluaranBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') === true
            || $this->user()?->can('viewWmsControl') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('tanggal_kembali') === '' || $this->input('tanggal_kembali') === null) {
            $this->merge(['tanggal_kembali' => today()->toDateString()]);
        }
    }

    public function rules(): array
    {
        return [
            'tanggal_kembali' => 'required|date',
            'baris' => 'required|array|min:1',
            'baris.*.status' => ['nullable', Rule::in([
                PengeluaranBarangDetail::KEMBALI,
                PengeluaranBarangDetail::TIDAK_KEMBALI,
            ])],
            'baris.*.kondisi_kembali' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return ['baris' => 'barang yang kembali', 'tanggal_kembali' => 'tanggal kembali'];
    }

    public function messages(): array
    {
        return ['baris.required' => 'Pilih minimal satu barang yang kembali.'];
    }
}
