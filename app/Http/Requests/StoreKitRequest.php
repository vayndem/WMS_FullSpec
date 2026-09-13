<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') === true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:40|unique:wms_kit,kode',
            'nama' => 'required|string|max:150',
            'bahan_hasil_id' => 'required|integer|exists:bahans,id',
            'jumlah_hasil' => 'required|numeric|gt:0',
            'catatan' => 'nullable|string|max:255',
            'komponen' => 'required|array|min:1',
            'komponen.*.bahan_id' => 'required|integer|distinct|exists:bahans,id',
            'komponen.*.jumlah' => 'required|numeric|gt:0',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $hasil = (int) $this->input('bahan_hasil_id');

            foreach ((array) $this->input('komponen', []) as $index => $komponen) {
                if ((int) ($komponen['bahan_id'] ?? 0) === $hasil) {
                    $validator->errors()->add("komponen.{$index}.bahan_id", 'Bahan hasil tidak boleh menjadi komponennya sendiri.');
                }
            }
        }];
    }
}
