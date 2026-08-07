<?php

namespace App\Http\Requests;

use App\Models\AssetCategory;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('asset_category') ? 'update' : 'create', $this->route('asset_category') ?: AssetCategory::class);
    }
    public function rules(): array
    {
        $id = $this->route('asset_category')?->id;
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('asset_categories', 'code')->ignore($id)],
            'name' => 'required|string|max:150',
            'asset_coa_id' => 'required|exists:chart_of_accounts,id',
            'accumulated_depreciation_coa_id' => 'required|different:asset_coa_id|exists:chart_of_accounts,id',
            'depreciation_expense_coa_id' => 'required|exists:chart_of_accounts,id',
            'disposal_gain_coa_id' => 'required|exists:chart_of_accounts,id',
            'disposal_loss_coa_id' => 'required|exists:chart_of_accounts,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $rules = [
                'asset_coa_id' => [['ASET', 'DEBIT']],
                'accumulated_depreciation_coa_id' => [['ASET', 'KREDIT']],
                'depreciation_expense_coa_id' => [['BEBAN', 'DEBIT']],
                'disposal_gain_coa_id' => [['PENDAPATAN', 'KREDIT']],
                'disposal_loss_coa_id' => [['BEBAN', 'DEBIT']],
            ];

            foreach ($rules as $field => $allowed) {
                $account = ChartOfAccount::find($this->input($field));
                if ($account && !$account->isUsableFor($allowed)) {
                    $validator->errors()->add($field, 'Akun harus aktif, postable, serta sesuai dengan fungsi kategori asset.');
                }
            }
        }];
    }
}
