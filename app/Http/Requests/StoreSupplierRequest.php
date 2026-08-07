<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Supplier;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Supplier::class);
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
