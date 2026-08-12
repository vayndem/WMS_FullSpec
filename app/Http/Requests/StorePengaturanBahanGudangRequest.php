<?php

namespace App\Http\Requests;

use App\Models\PengaturanBahanGudang;
use Illuminate\Foundation\Http\FormRequest;

class StorePengaturanBahanGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PengaturanBahanGudang::class) ?? false;
    }
    public function rules(): array
    {
        return ['gudang_id' => 'required|exists:gudangs,id', 'bahan_id' => 'required|exists:bahans,id', 'stok_minimum' => 'required|numeric|min:0', 'stok_maksimum' => 'required|numeric|gte:stok_minimum', 'stok_pengaman' => 'required|numeric|min:0', 'titik_pemesanan' => 'required|numeric|min:0', 'aktif' => 'nullable|boolean'];
    }
}
