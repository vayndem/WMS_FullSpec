<?php

namespace App\Http\Requests;

use App\Models\InvoiceLpb;
use App\Models\Lpb;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoiceLpbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InvoiceLpb::class) ?? false;
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
        return [
            'no_invoice'              => 'required|string|max:100|unique:invoice_lpbs,no_invoice',
            'lpb_ids'                 => 'required|array|min:1',
            'lpb_ids.*'               => 'required|integer|distinct|exists:lpbs,id',
            'kode_supplier'           => 'required|exists:suppliers,id',
            'tanggal'                 => 'required|date',
            'tgl_deadline_pembayaran' => 'nullable|date',
            'is_ppn'                  => 'required|boolean',
            'diskon'                  => 'nullable|numeric|min:0',
            'ongkir'                  => 'nullable|numeric|min:0',
            'note'                    => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $lpbs = Lpb::query()
                ->whereIn('id', $this->input('lpb_ids', []))
                ->whereNull('no_invoice')
                ->where('status', Lpb::POSTED)
                ->with('pembelian:id,no_po,supplier_id')
                ->get();

            $supplierIds = $lpbs->pluck('pembelian.supplier_id')->filter()->unique();
            if (
                $lpbs->count() !== count($this->input('lpb_ids', []))
                || $supplierIds->count() !== 1
                || (int) $supplierIds->first() !== (int) $this->input('kode_supplier')
            ) {
                $validator->errors()->add(
                    'lpb_ids',
                    'LPB/BAP harus tersedia dan seluruhnya berasal dari supplier yang dipilih.'
                );
            }
        }];
    }
}
