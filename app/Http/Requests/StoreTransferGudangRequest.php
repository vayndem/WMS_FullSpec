<?php

namespace App\Http\Requests;

use App\Models\TransferGudang;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TransferGudang::class) ?? false;
    }
    public function rules(): array
    {
        return ['nomor_transfer' => 'required|string|max:50|unique:transfer_gudangs,nomor_transfer', 'tanggal' => 'required|date', 'gudang_asal_id' => 'required|different:gudang_tujuan_id|exists:gudangs,id', 'gudang_tujuan_id' => 'required|exists:gudangs,id', 'keterangan' => 'nullable|string|max:2000', 'details' => 'required|array|min:1', 'details.*.bahan_id' => 'required|distinct|exists:bahan,id', 'details.*.jumlah' => 'required|numeric|gt:0', 'details.*.keterangan' => 'nullable|string|max:1000'];
    }
}
