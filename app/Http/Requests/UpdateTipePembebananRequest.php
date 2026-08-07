<?php

namespace App\Http\Requests;

use App\Models\TipePembebanan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTipePembebananRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tipe = $this->route('tipe_pembebanan') ?? $this->route('id');
        if (is_numeric($tipe)) {
            $tipe = TipePembebanan::find($tipe);
        }

        return $tipe ? ($this->user()?->can('update', $tipe) ?? false) : false;
    }

    public function rules(): array
    {
        $id = $this->route('tipe_pembebanan') ?? $this->route('id');
        if (is_object($id)) {
            $id = $id->id;
        }

        return [
            'nama_tipe'  => 'required|string|max:100|unique:tipe_pembebanans,nama_tipe,' . $id,
            'keterangan' => 'nullable|string',
        ];
    }
}
