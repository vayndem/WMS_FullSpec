<?php

namespace App\Http\Requests;

use App\Models\BaganAkun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBaganAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coa = $this->route('chart_of_account') ?? $this->route('id');
        if (is_numeric($coa)) {
            $coa = BaganAkun::find($coa);
        }

        return $coa ? ($this->user()?->can('update', $coa) ?? false) : false;
    }

    public function rules(): array
    {
        $id = $this->route('chart_of_account') ?? $this->route('id');
        if (is_object($id)) {
            $id = $id->id;
        }

        return [
            'kode_akun'     => 'required|string|max:50|unique:wms_bagan_akun,kode_akun,' . $id,
            'nama_akun'     => 'required|string|max:150',
            'kategori_akun' => 'required|in:ASET,LIABILITAS,EKUITAS,PENDAPATAN,BEBAN',
            'posisi_normal' => 'required|in:DEBIT,KREDIT',
            'is_active'     => 'sometimes|boolean',
            'is_postable'   => 'sometimes|boolean',
            'is_cash_bank'  => 'sometimes|boolean',
            'klasifikasi_fiskal' => ['sometimes', Rule::in(array_keys(BaganAkun::KLASIFIKASI_FISKAL))],
            'keterangan'    => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->boolean('is_cash_bank') && (
                $this->input('kategori_akun') !== 'ASET'
                || $this->input('posisi_normal') !== 'DEBIT'
                || !$this->boolean('is_active', true)
                || !$this->boolean('is_postable', true)
            )) {
                $validator->errors()->add('is_cash_bank', 'Akun Kas/Bank wajib berupa ASET aktif, postable, dengan posisi normal DEBIT.');
            }
        }];
    }
}
