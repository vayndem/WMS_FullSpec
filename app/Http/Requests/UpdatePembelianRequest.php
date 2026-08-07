<?php

namespace App\Http\Requests;

use App\Models\Pembelian;
use App\Models\RequestDetail;
use App\Models\Pembeliandetail;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $noPo = $this->route('no_po') ?? $this->route('pembelian');

        $pembelian = $noPo instanceof Pembelian
            ? $noPo
            : Pembelian::where('no_po', $noPo)->first();

        return $pembelian ? ($this->user()?->can('update', $pembelian) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_id'     => $this->supplier_id ?? $this->id_suplier,
            'untuk_perhatian' => $this->untuk_perhatian ?? $this->untukperhatian ?? '-',
            'term'            => $this->term ?? $this->term_pembayaran ?? '-',
            'diskon'          => $this->diskon ?? 0,
            'ongkir'          => $this->ongkir ?? 0,
            'input_label'     => $this->input_label ?? $this->inputlabel ?? 'Freight Handling',
            'jenis'           => 0,
            'is_ppn'          => $this->boolean('is_ppn'),
        ]);
    }

    public function rules(): array
    {
        $noPoTarget = $this->route('no_po') ?? $this->route('pembelian');

        return [
            'tanggal'                     => 'required|date',
            'supplier_id'                 => 'required|exists:suppliers,id',
            'no_order'                    => 'nullable|string|max:250',
            'untuk_perhatian'             => 'nullable|string|max:250',
            'term'                        => 'nullable|string|max:250',
            'term_pembayaran'             => 'nullable|string|max:250',
            'notes'                       => 'nullable|string',
            'is_ppn'                      => 'required|boolean',
            'diskon'                      => 'nullable|numeric|min:0',
            'ongkir'                      => 'nullable|numeric|min:0',
            'input_label'                 => 'nullable|string|max:100',
            'details'                     => 'required|array|min:1',
            'details.*.bahan_id'          => 'required|exists:bahan,id',
            'details.*.harga'             => 'required|numeric|min:0',
            'details.*.request_detail_id' => 'nullable|exists:request_details,id',
            'details.*.jumlah'            => [
                'required',
                'numeric',
                'gt:0',
                function ($attribute, $value, $fail) use ($noPoTarget) {
                    $parts = explode('.', $attribute);
                    $key = $parts[1] ?? null;

                    if ($key !== null) {
                        $allDetails = $this->input('details', []);
                        $itemData = $allDetails[$key] ?? null;
                        $reqDetailId = $itemData['request_detail_id'] ?? null;

                        if ($reqDetailId) {
                            $reqDetail = RequestDetail::find($reqDetailId);
                            if ($reqDetail) {
                                $existingRealisasiThisPo = 0;
                                if ($noPoTarget) {
                                    $existingRealisasiThisPo = Pembeliandetail::where('no_po', $noPoTarget)
                                        ->where('request_detail_id', $reqDetailId)
                                        ->sum('jumlah');
                                }

                                $sisaKuota = ($reqDetail->jumlah_acc - $reqDetail->realisasi) + $existingRealisasiThisPo;
                                if ($value > $sisaKuota) {
                                    $fail("Jumlah pembelian ({$value}) melebihi sisa kuota ACC ({$sisaKuota}).");
                                }
                            }
                        }
                    }
                }
            ],
        ];
    }
}
