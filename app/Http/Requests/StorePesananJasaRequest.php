<?php

namespace App\Http\Requests;

use App\Models\Aset;
use App\Models\KategoriJasa;
use App\Models\PesananJasa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePesananJasaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('service_purchase') ? 'update' : 'create', $this->route('service_purchase') ?: PesananJasa::class);
    }
    public function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'untuk_perhatian' => 'nullable|string|max:250',
            'term' => 'nullable|string|max:250',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.service_category_id' => 'required|exists:wms_kategori_jasa,id',
            'items.*.description' => 'required|string|max:2000',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:30',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.aset_id' => 'nullable|integer|exists:wms_asets,id',
            'items.*.tambahan_umur_bulan' => 'nullable|integer|min:0|max:600',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $category = KategoriJasa::find($item['service_category_id'] ?? 0);
                if ($category && !$category->kategori_bahan_id) {
                    $validator->errors()->add("items.$index.service_category_id", 'Kategori jasa belum terhubung ke kategori bahan.');
                }

                if (!$category || !$category->dikapitalisasi()) {
                    if (!empty($item['aset_id'])) {
                        $validator->errors()->add("items.$index.aset_id",
                            'Aset hanya boleh diisi untuk kategori jasa yang perlakuannya Kapitalisasi.');
                    }
                    continue;
                }

                $aset = Aset::find($item['aset_id'] ?? 0);

                if (!$aset) {
                    $validator->errors()->add("items.$index.aset_id",
                        "Jasa {$category->name} berperlakuan Kapitalisasi, jadi aset tujuannya wajib dipilih.");
                    continue;
                }

                if ($aset->status !== 'ACTIVE') {
                    $validator->errors()->add("items.$index.aset_id",
                        "Aset {$aset->nomor_aset} tidak aktif, jasanya tidak dapat dikapitalisasi ke sana.");
                }

                if (!$aset->category?->akun_aset_id) {
                    $validator->errors()->add("items.$index.aset_id",
                        "Kategori aset {$aset->nomor_aset} belum punya mapping akun aset.");
                }
            }
        }];
    }
}
