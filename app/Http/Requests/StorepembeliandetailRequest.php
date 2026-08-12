<?php

namespace App\Http\Requests;

use App\Models\Pembelian;
use App\Models\RequestDetail;
use Illuminate\Foundation\Http\FormRequest;

class StorePembelianDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $noPo = $this->route('no_po');
        $pembelian = $noPo instanceof Pembelian ? $noPo : Pembelian::where('no_po', $noPo)->first();

        return $pembelian ? ($this->user()?->can('update', $pembelian) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bahan_id'          => $this->bahan_id ?? $this->id_bahan,
            'request_detail_id' => $this->request_detail_id ?? $this->id_permintaan,
        ]);
    }

    public function rules(): array
    {
        return [
            'bahan_id'          => 'required|exists:bahans,id',
            'jumlah'            => [
                'required',
                'numeric',
                'gt:0',
                function ($attribute, $value, $fail) {
                    if ($this->request_detail_id) {
                        $reqDetail = RequestDetail::find($this->request_detail_id);
                        if ($reqDetail) {
                            $sisaKuota = $reqDetail->jumlah_acc - $reqDetail->realisasi;
                            if ($value > $sisaKuota) {
                                $fail("Jumlah ({$value}) melebihi sisa kuota ACC ({$sisaKuota}).");
                            }
                        }
                    }
                }
            ],
            'harga'             => 'required|numeric|min:0',
            'request_detail_id' => 'nullable|exists:request_details,id',
        ];
    }
}
