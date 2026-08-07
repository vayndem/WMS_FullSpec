<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGudangRequest extends StoreGudangRequest
{
    public function authorize(): bool
    {
        $model = $this->route('gudang');
        return $model && ($this->user()?->can('update', $model) ?? false);
    }
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['kode'] = ['required', 'string', 'max:30', Rule::unique('gudangs', 'kode')->ignore($this->route('gudang'))];
        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $gudang = $this->route('gudang');
            if (!$gudang || $gudang->jenis === $this->input('jenis')) {
                return;
            }

            if ($gudang->stok()->exists() || $gudang->lpbs()->exists() || $gudang->pembelians()->exists()) {
                $validator->errors()->add('jenis', 'Jenis gudang tidak dapat diubah setelah gudang memiliki stok atau transaksi.');
            }
        });
    }
}
