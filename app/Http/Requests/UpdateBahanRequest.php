<?php

namespace App\Http\Requests;

use App\Models\Bahan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bahan = $this->route('bahan');
        return $bahan instanceof Bahan && ($this->user()?->can('update', $bahan) ?? false);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:200', Rule::unique('bahan', 'nama')->ignore($this->route('bahan'))],
            'keterangan_bahan' => ['nullable', 'string', 'max:200'],
            // Kategori menentukan mapping COA. Setelah bahan terbentuk nilainya
            // tidak boleh dipindah diam-diam ke kelompok akun lain.
            'kategori' => [
                'required',
                'integer',
                'exists:kategoribahan,id',
                Rule::in([(int) $this->route('bahan')->kategori]),
            ],
            'satuan' => ['required', 'string', 'max:50'],
            'berat_kecil' => ['nullable', 'numeric', 'gt:0', 'max:1000000000'],
            'satuan_kecil' => ['nullable', 'string', 'max:11', 'different:satuan'],
            'tipe_gudang' => ['required', 'integer', 'exists:gudangs,id'],
            'planning' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $hasFactor = $this->filled('berat_kecil') && (float) $this->input('berat_kecil') > 1;
            $hasSmallUnit = $this->filled('satuan_kecil');
            if ($hasFactor !== $hasSmallUnit) {
                $validator->errors()->add(
                    $hasFactor ? 'satuan_kecil' : 'berat_kecil',
                    'Jumlah konversi dan nama satuan kecil harus diisi bersama.'
                );
            }
        }];
    }

    public function messages(): array
    {
        return [
            'kategori.in' => 'Kategori bahan sudah dikunci karena menentukan mapping COA dan tidak dapat diubah.',
        ];
    }
}
