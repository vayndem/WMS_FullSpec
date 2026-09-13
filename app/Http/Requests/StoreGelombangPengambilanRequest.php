<?php

namespace App\Http\Requests;

use App\Models\GelombangPengambilan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGelombangPengambilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') === true
            && $this->user()->canAccessGudang((int) $this->input('gudang_id'));
    }

    public function rules(): array
    {
        return [
            'gudang_id' => 'required|integer|exists:gudangs,id',
            'strategi' => ['required', Rule::in(array_keys(GelombangPengambilan::STRATEGI))],
            'catatan' => 'nullable|string|max:255',
            'pick_ids' => 'required|array|min:1',
            'pick_ids.*' => 'required|integer|distinct|exists:wms_pesanan_pengambilan,id',
        ];
    }

    public function attributes(): array
    {
        return ['pick_ids' => 'perintah pengambilan'];
    }
}
