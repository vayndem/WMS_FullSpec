<?php

namespace App\Http\Requests;

use App\Models\TipePembebanan;
use Illuminate\Foundation\Http\FormRequest;

class StoreTipePembebananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TipePembebanan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_tipe'  => 'required|string|max:100|unique:tipe_pembebanans,nama_tipe',
            'keterangan' => 'nullable|string',
        ];
    }
}
