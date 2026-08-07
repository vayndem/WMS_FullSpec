<?php

namespace App\Http\Requests;

use App\Models\Invoicelpbdetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateinvoicelpbdetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = $this->route('invoicelpbdetail') ?? $this->route('id');
        $detail = Invoicelpbdetail::with('invoice')->find($id);

        return $detail && $detail->invoice ? ($this->user()?->can('update', $detail->invoice) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jumlah_pembayaran'   => $this->jumlah_pembayaran ?? 0,
            'potongan_pph23'      => $this->potongan_pph23 ?? 0,
            'potongan_materai'    => $this->potongan_materai ?? 0,
            'biaya_transfer_bank' => $this->biaya_transfer_bank ?? 0,
            'selisih_bayar'       => $this->selisih_bayar ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal_pembayaran'  => 'required|date',
            'metode_pembayaran'   => 'required|string|max:150',
            'coa_kas_bank_id'     => 'required|integer|exists:chart_of_accounts,id',
            'jumlah_pembayaran'   => 'nullable|numeric|min:0',
            'potongan_pph23'      => 'nullable|numeric|min:0',
            'potongan_materai'    => ['nullable', 'numeric', Rule::in([0, 10000])],
            'biaya_transfer_bank' => 'nullable|numeric|min:0',
            'selisih_bayar'       => 'nullable|numeric|min:0',
            'keterangan'          => 'nullable|string',
        ];
    }
}
