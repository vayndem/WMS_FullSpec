<?php

namespace App\Http\Requests;

use App\Models\Request as RequestModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorerequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RequestModel::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'no_request'          => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}-[A-Z]{2,3}-\d{7}$/', 'unique:requests,no_request'],
            'items'                => 'required|array|min:1',
            'items.*.bahan_id'     => 'nullable|exists:bahan,id',
            'items.*.nama_barang'  => 'required|string|max:255',
            'items.*.jumlah_minta' => 'required|numeric|gt:0',
            'items.*.keterangan'   => 'nullable|string',
            'items.*.kategori'     => 'required|exists:kategoribahan,id',
            'items.*.satuan'       => 'required|string|max:250',
            'items.*.berat_kecil'  => 'nullable|numeric|gt:0',
            'items.*.satuan_kecil' => 'nullable|string|max:11',
            'items.*.tipe_gudang'  => 'required|exists:admin_namagudang,id',
            'items.*.tipe_barang'  => 'required|exists:kategoribahan,id',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                if ((int) ($item['kategori'] ?? 0) !== (int) ($item['tipe_barang'] ?? 0)) {
                    $validator->errors()->add(
                        "items.$index.kategori",
                        'Kategori bahan harus terhubung dengan kategori pada master Kategori & Mapping.'
                    );
                }

                $hasFactor = isset($item['berat_kecil']) && (float) $item['berat_kecil'] > 1;
                $hasSmallUnit = filled($item['satuan_kecil'] ?? null);
                if ($hasFactor !== $hasSmallUnit) {
                    $validator->errors()->add(
                        "items.$index.satuan_kecil",
                        'Jumlah konversi dan nama satuan kecil harus diisi bersama.'
                    );
                }
            }
        }];
    }
}
