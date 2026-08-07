<?php

namespace App\Http\Requests;

use App\Models\Pembeliandetail;
use App\Models\RequestDetail;
use Illuminate\Foundation\Http\FormRequest;

class UpdatepembeliandetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $detail = $this->route('pembeliandetail');
        $pembeliandetail = $detail instanceof Pembeliandetail
            ? $detail
            : Pembeliandetail::with('pembelian')->find($detail);

        return $pembeliandetail && $pembeliandetail->pembelian
            ? ($this->user()?->can('update', $pembeliandetail->pembelian) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
                function ($attribute, $value, $fail) {
                    $detailParam = $this->route('pembeliandetail');
                    $detail = $detailParam instanceof Pembeliandetail
                        ? $detailParam
                        : Pembeliandetail::find($detailParam);

                    if ($detail && $detail->request_detail_id) {
                        $reqDetail = RequestDetail::find($detail->request_detail_id);
                        if ($reqDetail) {
                            $sisaTersedia = ($reqDetail->jumlah_acc - $reqDetail->realisasi) + $detail->jumlah;
                            if ($value > $sisaTersedia) {
                                $fail("Jumlah baru ({$value}) melebihi batas sisa kuota ACC ({$sisaTersedia}).");
                            }
                        }
                    }
                }
            ],
            'harga' => 'required|numeric|min:0',
        ];
    }
}
