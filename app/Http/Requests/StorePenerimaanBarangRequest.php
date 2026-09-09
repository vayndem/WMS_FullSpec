<?php

namespace App\Http\Requests;

use App\Models\Bahan;
use App\Models\LotPersediaan;
use App\Models\PenerimaanBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StorePenerimaanBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PenerimaanBarang::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'id_lpb'                           => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', 'unique:wms_penerimaan_barang,id_lpb'],
            'tanggal'                          => 'required|date',
            'no_po'                            => 'required|string|exists:wms_pesanan_pembelian,no_po',
            'no_sj'                            => 'required|string|max:250',
            'no_invoice'                       => 'nullable|string|max:50',
            'jenis_lpb'                        => 'nullable|integer',
            'details'                          => 'required|array|min:1',
            'details.*.id_bahan'               => 'required|integer|exists:bahans,id',
            'details.*.id_kategori'            => 'required|integer|exists:kategori_bahans,id',
            'details.*.jumlah_barang_diterima' => 'required|numeric|gt:0',
            'details.*.lot_number'             => 'nullable|string|max:80',
            'details.*.expires_at'             => 'nullable|date',
            'confirm_over_receive'             => 'nullable|boolean',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $details = (array) $this->input('details', []);
            $bahans = Bahan::whereIn('id', collect($details)->pluck('id_bahan')->filter()->unique())
                ->get(['id', 'nama', 'wajib_lot', 'wajib_expiry'])->keyBy('id');

            foreach ($details as $index => $detail) {
                $bahan = $bahans->get($detail['id_bahan'] ?? null);
                if (!$bahan) {
                    continue;
                }
                if ($bahan->wajib_lot && blank($detail['lot_number'] ?? null)) {
                    $validator->errors()->add("details.{$index}.lot_number", "Bahan {$bahan->nama} wajib dicatat dengan nomor lot.");
                }
                if ($bahan->wajib_expiry && blank($detail['expires_at'] ?? null)) {
                    $validator->errors()->add("details.{$index}.expires_at", "Bahan {$bahan->nama} wajib dicatat dengan tanggal kedaluwarsa.");
                }

                if (filled($detail['lot_number'] ?? null) && filled($detail['expires_at'] ?? null)) {
                    $lot = LotPersediaan::where('bahan_id', $bahan->id)->where('lot_number', $detail['lot_number'])->first();
                    if ($lot?->expires_at && !$lot->expires_at->isSameDay(Carbon::parse($detail['expires_at']))) {
                        $validator->errors()->add(
                            "details.{$index}.expires_at",
                            "Lot {$lot->lot_number} sudah terdaftar kedaluwarsa {$lot->expires_at->format('d-m-Y')}. Pakai nomor lot lain kalau memang batch berbeda."
                        );
                    }
                }
            }
        }];
    }
}
