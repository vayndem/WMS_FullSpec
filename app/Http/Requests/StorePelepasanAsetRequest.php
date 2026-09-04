<?php

namespace App\Http\Requests;

use App\Models\BaganAkun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelepasanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispose', $this->route('aset'));
    }
    public function rules(): array
    {
        return [
            'disposal_date' => 'required|date',
            'disposal_type' => 'required|in:SALE,WRITE_OFF',
            'proceeds' => 'nullable|required_if:disposal_type,SALE|numeric|min:0',
            'cash_bank_coa_id' => ['nullable', Rule::requiredIf($this->input('disposal_type') === 'SALE'), 'exists:wms_bagan_akun,id'],
            'reason' => 'required|string|max:1000',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('disposal_type') !== 'SALE') {
                return;
            }
            $account = BaganAkun::find($this->input('cash_bank_coa_id'));
            if ($account && !$account->isUsableFor([['ASET', 'DEBIT']], true)) {
                $validator->errors()->add('cash_bank_coa_id', 'Hasil penjualan wajib masuk akun Kas/Bank yang aktif dan postable.');
            }
        }];
    }
}
