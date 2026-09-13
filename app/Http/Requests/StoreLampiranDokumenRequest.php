<?php

namespace App\Http\Requests;

use App\Models\LampiranDokumen;
use App\Services\LampiranService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLampiranDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'lampiran_type' => ['required', Rule::in(array_keys(LampiranService::INDUK))],
            'lampiran_id' => 'required|integer|min:1',
            'kategori' => ['required', Rule::in(array_keys(LampiranDokumen::KATEGORI))],
            'keterangan' => 'nullable|string|max:255',
            'berkas' => [
                'required',
                'file',
                'max:' . LampiranService::UKURAN_MAKS_KB,
                'mimes:' . implode(',', LampiranService::MIME_DIIZINKAN),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'berkas' => 'berkas lampiran',
            'lampiran_type' => 'jenis dokumen',
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.max' => 'Ukuran berkas maksimal ' . (LampiranService::UKURAN_MAKS_KB / 1024) . ' MB.',
            'berkas.mimes' => 'Format yang diizinkan: ' . implode(', ', LampiranService::MIME_DIIZINKAN) . '.',
        ];
    }
}
