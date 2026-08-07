<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');
        return $this->user()->can('update', $supplier);
    }

    public function rules(): array
    {
        return [
            'nama'       => 'required|string|max:200',
            'alamat'     => 'required|string|max:200',
            'npwp'       => 'nullable|string|max:50',
            'telp'       => 'required|string|max:50',
            'up'         => 'nullable|string|max:200',
            'pembayaran' => 'required|string|max:200',
        ];
    }
}
