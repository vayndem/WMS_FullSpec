<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelesaikanDataPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('selesaikan', $this->route('pesanan')) ?? false;
    }

    public function rules(): array
    {
        return [
            'jumlah_selesai' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function attributes(): array
    {
        return ['jumlah_selesai' => 'jumlah hasil produksi'];
    }
}
