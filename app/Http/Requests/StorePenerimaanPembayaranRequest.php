<?php

namespace App\Http\Requests;

use App\Models\BaganAkun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenerimaanPembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('terimaPembayaran', $this->route('faktur')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'coa_kas_bank_id' => ['required', 'integer', Rule::exists('wms_bagan_akun', 'id')
                ->where(fn ($query) => $query->where('is_cash_bank', 1)->where('is_active', 1)->where('is_postable', 1))],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'referensi' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return ['coa_kas_bank_id' => 'akun kas/bank'];
    }
}
