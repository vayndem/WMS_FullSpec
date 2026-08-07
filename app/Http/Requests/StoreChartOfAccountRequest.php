<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChartOfAccount::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode_akun'     => 'required|string|max:50|unique:chart_of_accounts,kode_akun',
            'nama_akun'     => 'required|string|max:150',
            'kategori_akun' => 'required|in:ASET,LIABILITAS,EKUITAS,PENDAPATAN,BEBAN',
            'posisi_normal' => 'required|in:DEBIT,KREDIT',
            'is_active'     => 'sometimes|boolean',
            'is_postable'   => 'sometimes|boolean',
            'is_cash_bank'  => 'sometimes|boolean',
            'keterangan'    => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->boolean('is_cash_bank') && (
                $this->input('kategori_akun') !== 'ASET'
                || $this->input('posisi_normal') !== 'DEBIT'
                || !$this->boolean('is_active', true)
                || !$this->boolean('is_postable', true)
            )) {
                $validator->errors()->add('is_cash_bank', 'Akun Kas/Bank wajib berupa ASET aktif, postable, dengan posisi normal DEBIT.');
            }
        }];
    }
}
