<?php

namespace App\Http\Requests;

use App\Models\FakturPembelian;
use App\Models\PembayaranFaktur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePembayaranFakturRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = FakturPembelian::find($this->input('invoice_lpb_id'));
        return $invoice ? ($this->user()?->can('pay', $invoice) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jumlah_pembayaran'           => $this->jumlah_pembayaran ?? 0,
            'potongan_pph'              => $this->potongan_pph ?? 0,
            'potongan_materai'            => $this->potongan_materai ?? 0,
            'biaya_transfer_bank'         => $this->biaya_transfer_bank ?? 0,
            'selisih_bayar'               => $this->selisih_bayar ?? 0,
            'jenis_selisih'               => (float) ($this->selisih_bayar ?? 0) > 0 ? $this->jenis_selisih : null,
            'uang_muka_dipakai'           => $this->uang_muka_sumber_payment_id ? ($this->uang_muka_dipakai ?? 0) : 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_number'              => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', 'unique:wms_pembayaran_faktur,payment_number'],
            'invoice_lpb_id'               => 'required|integer|exists:wms_faktur_pembelian,id',
            'tanggal_pembayaran'           => 'required|date',
            'metode_pembayaran'            => 'required|string|max:150',
            'coa_kas_bank_id'              => ['required', 'integer', Rule::exists('wms_bagan_akun', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)->where('is_cash_bank', 1)
            )],
            'jumlah_pembayaran'            => 'nullable|numeric|min:0',
            'potongan_pph'               => 'nullable|numeric|min:0',
            'potongan_materai'             => ['nullable', 'numeric', Rule::in([0, 10000])],
            'biaya_transfer_bank'          => 'nullable|numeric|min:0',
            'selisih_bayar'                => 'nullable|numeric|min:0',
            'jenis_selisih'                => ['nullable', Rule::requiredIf(fn() => (float) $this->input('selisih_bayar', 0) > 0), 'in:PENDAPATAN_SELISIH,BEBAN_SELISIH,UANG_MUKA_SUPPLIER'],
            'coa_selisih_id'               => ['nullable', Rule::requiredIf(fn() => (float) $this->input('selisih_bayar', 0) > 0), 'integer', Rule::exists('wms_bagan_akun', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)
            )],
            'uang_muka_sumber_payment_id'  => ['nullable', 'integer', 'exists:wms_pembayaran_faktur,id'],
            'uang_muka_dipakai'            => [
                'nullable', 'numeric', 'min:0',
                Rule::requiredIf(fn() => filled($this->input('uang_muka_sumber_payment_id'))),
            ],
            'keterangan'                   => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $sourceId = $this->input('uang_muka_sumber_payment_id');
            if (!$sourceId || $validator->errors()->isNotEmpty()) {
                return;
            }

            $source = PembayaranFaktur::with('invoice')->find($sourceId);
            $invoice = FakturPembelian::find($this->input('invoice_lpb_id'));
            $requested = (float) $this->input('uang_muka_dipakai', 0);

            if (!$source || $source->status !== PembayaranFaktur::POSTED || $source->jenis_selisih !== 'UANG_MUKA_SUPPLIER') {
                $validator->errors()->add('uang_muka_sumber_payment_id', 'Sumber uang muka tidak valid atau bukan pembayaran uang muka supplier yang aktif.');
                return;
            }

            $supplierUangMuka = (int) ($source->supplier_id ?: $source->invoice?->kode_supplier);

            if (!$invoice || $supplierUangMuka === 0 || $supplierUangMuka !== (int) $invoice->kode_supplier) {
                $validator->errors()->add('uang_muka_sumber_payment_id', 'Uang muka hanya bisa dipakai untuk invoice dari supplier yang sama.');
                return;
            }

            if ($requested <= 0 || $requested > $source->sisaUangMuka() + 0.01) {
                $validator->errors()->add('uang_muka_dipakai', 'Nominal uang muka yang dipakai melebihi sisa uang muka supplier yang tersedia.');
            }
        }];
    }
}
