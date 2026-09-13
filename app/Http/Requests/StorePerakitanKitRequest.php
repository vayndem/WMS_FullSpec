<?php

namespace App\Http\Requests;

use App\Models\PerakitanKit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerakitanKitRequest extends FormRequest
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
            'jenis' => ['required', Rule::in([PerakitanKit::RAKIT, PerakitanKit::URAI])],
            'tanggal' => 'required|date',
            'jumlah_kit' => 'required|numeric|gt:0',
            'catatan' => 'nullable|string|max:255',
        ];
    }
}
