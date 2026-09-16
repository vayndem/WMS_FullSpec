<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFakturPenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('faktur', $this->route('suratJalan')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'no_faktur_pajak' => ['nullable', 'string', 'regex:/^\\d{3}\\.\\d{3}-\\d{2}\\.\\d{8}$/'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_faktur_pajak.regex' => 'Format nomor seri faktur pajak harus 000.000-00.00000000.',
        ];
    }
}
