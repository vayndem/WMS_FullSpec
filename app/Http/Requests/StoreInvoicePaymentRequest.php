<?php

namespace App\Http\Requests;

use App\Models\InvoiceLpb;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = InvoiceLpb::find($this->input('invoice_lpb_id'));
        return $invoice ? ($this->user()?->can('pay', $invoice) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jumlah_pembayaran'   => $this->jumlah_pembayaran ?? 0,
            'potongan_pph23'      => $this->potongan_pph23 ?? 0,
            'potongan_materai'    => $this->potongan_materai ?? 0,
            'biaya_transfer_bank' => $this->biaya_transfer_bank ?? 0,
            'selisih_bayar'       => $this->selisih_bayar ?? 0,
            'jenis_selisih'       => (float) ($this->selisih_bayar ?? 0) > 0 ? $this->jenis_selisih : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_number'       => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', 'unique:invoice_payments,payment_number'],
            'invoice_lpb_id'      => 'required|integer|exists:invoice_lpbs,id',
            'tanggal_pembayaran'  => 'required|date',
            'metode_pembayaran'   => 'required|string|max:150',
            'coa_kas_bank_id'     => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)->where('is_cash_bank', 1)
            )],
            'jumlah_pembayaran'   => 'nullable|numeric|min:0',
            'potongan_pph23'      => 'nullable|numeric|min:0',
            'potongan_materai'    => ['nullable', 'numeric', Rule::in([0, 10000])],
            'biaya_transfer_bank' => 'nullable|numeric|min:0',
            'selisih_bayar'       => 'nullable|numeric|min:0',
            'jenis_selisih'       => ['nullable', Rule::requiredIf(fn() => (float) $this->input('selisih_bayar', 0) > 0), 'in:PENDAPATAN_SELISIH,BEBAN_SELISIH,UANG_MUKA_SUPPLIER'],
            'coa_selisih_id'      => ['nullable', Rule::requiredIf(fn() => (float) $this->input('selisih_bayar', 0) > 0), 'integer', Rule::exists('chart_of_accounts', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)
            )],
            'keterangan'          => 'nullable|string',
        ];
    }
}
