<?php

namespace App\Http\Requests;

use App\Models\KategoriAset;
use App\Models\BaganAkun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKategoriAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('kategori_aset') ? 'update' : 'create', $this->route('kategori_aset') ?: KategoriAset::class);
    }
    public function rules(): array
    {
        $id = $this->route('kategori_aset')?->id;
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('wms_kategori_asets', 'code')->ignore($id)],
            'name' => 'required|string|max:150',
            'akun_aset_id' => 'required|exists:wms_bagan_akun,id',
            'accumulated_depreciation_coa_id' => 'required|different:akun_aset_id|exists:wms_bagan_akun,id',
            'depreciation_expense_coa_id' => 'required|exists:wms_bagan_akun,id',
            'disposal_gain_coa_id' => 'required|exists:wms_bagan_akun,id',
            'disposal_loss_coa_id' => 'required|exists:wms_bagan_akun,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $rules = [
                'akun_aset_id' => [['ASET', 'DEBIT']],
                'accumulated_depreciation_coa_id' => [['ASET', 'KREDIT']],
                'depreciation_expense_coa_id' => [['BEBAN', 'DEBIT']],
                'disposal_gain_coa_id' => [['PENDAPATAN', 'KREDIT']],
                'disposal_loss_coa_id' => [['BEBAN', 'DEBIT']],
            ];

            foreach ($rules as $field => $allowed) {
                $account = BaganAkun::find($this->input($field));
                if ($account && !$account->isUsableFor($allowed)) {
                    $validator->errors()->add($field, 'Akun harus aktif, postable, serta sesuai dengan fungsi kategori aset.');
                }
            }
        }];
    }
}
