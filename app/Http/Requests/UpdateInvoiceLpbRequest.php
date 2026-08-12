<?php

namespace App\Http\Requests;

use App\Models\InvoiceLpb;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceLpbRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = $this->route('invoicelpb') ?? $this->route('id');
        $invoice = InvoiceLpb::find($id);

        return $invoice ? ($this->user()?->can('update', $invoice) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_supplier' => $this->kode_supplier ?? $this->supplier_id ?? $this->id_suplier,
            'diskon'        => $this->diskon ?? 0,
            'ongkir'        => $this->ongkir ?? 0,
            'pph'           => $this->pph ?? 0,
            'is_ppn'        => $this->boolean('is_ppn'),
        ]);
    }

    public function rules(): array
    {
        $idTarget = $this->route('invoicelpb') ?? $this->route('id');

        return [
            'no_invoice'              => 'required|string|max:100|unique:invoice_lpbs,no_invoice,' . $idTarget,
            'kode_supplier'           => 'required|exists:suppliers,id',
            'lpb_ids'                 => 'required|array|min:1',
            'lpb_ids.*'               => 'required|integer|distinct|exists:lpbs,id',
            'tanggal'                 => 'required|date',
            'tgl_deadline_pembayaran' => 'nullable|date',
            'is_ppn'                  => 'required|boolean',
            'diskon'                  => 'nullable|numeric|min:0',
            'ongkir'                  => 'nullable|numeric|min:0',
            'note'                    => 'nullable|string',
        ];
    }
}
