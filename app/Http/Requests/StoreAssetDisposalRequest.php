<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispose', $this->route('asset'));
    }
    public function rules(): array
    {
        return [
            'disposal_date' => 'required|date',
            'disposal_type' => 'required|in:SALE,WRITE_OFF',
            'proceeds' => 'nullable|required_if:disposal_type,SALE|numeric|min:0',
            'cash_bank_coa_id' => ['nullable', Rule::requiredIf($this->input('disposal_type') === 'SALE'), 'exists:chart_of_accounts,id'],
            'reason' => 'required|string|max:1000',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('disposal_type') !== 'SALE') {
                return;
            }
            $account = ChartOfAccount::find($this->input('cash_bank_coa_id'));
            if ($account && !$account->isUsableFor([['ASET', 'DEBIT']], true)) {
                $validator->errors()->add('cash_bank_coa_id', 'Hasil penjualan wajib masuk akun Kas/Bank yang aktif dan postable.');
            }
        }];
    }
}
