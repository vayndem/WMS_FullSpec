<?php

namespace App\Http\Requests;

use App\Models\FakturPembelian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFakturPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = $this->route('faktur_pembelian') ?? $this->route('id');
        $invoice = FakturPembelian::find($id);

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
        $idTarget = $this->route('faktur_pembelian') ?? $this->route('id');

        return [
            'no_invoice'              => 'required|string|max:100|unique:wms_faktur_pembelian,no_invoice,' . $idTarget,
            'kode_supplier'           => 'required|exists:suppliers,id',
            'lpb_ids'                 => 'required|array|min:1',
            'lpb_ids.*'               => 'required|integer|distinct|exists:wms_penerimaan_barang,id',
            'tanggal'                 => 'required|date',
            'tgl_deadline_pembayaran' => 'nullable|date',
            'is_ppn'                  => 'required|boolean',
            'no_faktur_pajak'         => [
                Rule::requiredIf(fn () => $this->boolean('is_ppn')),
                'nullable', 'string', 'max:30',
                'regex:/^\d{3}\.\d{3}-\d{2}\.\d{8}$/',
            ],
            'diskon'                  => 'nullable|numeric|min:0',
            'ongkir'                  => 'nullable|numeric|min:0',
            'note'                    => 'nullable|string',
        ];
    }
}
