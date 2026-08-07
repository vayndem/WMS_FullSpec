<?php

namespace App\Http\Requests;

use App\Models\Gudang;
use Illuminate\Foundation\Http\FormRequest;

class StoreGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Gudang::class) ?? false;
    }
    public function rules(): array
    {
        return ['kode' => 'required|string|max:30|unique:gudangs,kode', 'nama' => 'required|string|max:150', 'jenis' => 'required|in:NORMAL,CONSIDER,RUSAK', 'alamat' => 'nullable|string|max:1000', 'aktif' => 'nullable|boolean', 'boleh_penerimaan' => 'nullable|boolean', 'boleh_npk' => 'nullable|boolean', 'boleh_transfer' => 'nullable|boolean', 'boleh_opname' => 'nullable|boolean'];
    }
}
