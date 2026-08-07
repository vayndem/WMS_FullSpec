<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('asset') ? 'update' : 'create', $this->route('asset') ?: Asset::class);
    }
    public function rules(): array
    {
        return [
            'asset_number' => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', Rule::unique('assets', 'asset_number')->ignore($this->route('asset'))],
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:180',
            'serial_number' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:150',
            'responsible_person' => 'nullable|string|max:150',
            'condition' => 'required|in:BAIK,PERLU_SERVIS,RUSAK',
            'acquisition_date' => 'required|date',
            'acquisition_type' => 'required|in:OPENING_BALANCE,CASH,CREDIT,GRANT,CORRECTION',
            'acquisition_credit_coa_id' => 'required|exists:chart_of_accounts,id',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'residual_value' => 'required|numeric|min:0|lte:acquisition_cost',
            'useful_life_months' => 'nullable|integer|min:1|max:1200',
            'depreciation_start_date' => 'nullable|date',
            'opening_accumulated_depreciation' => 'nullable|numeric|min:0|lte:acquisition_cost',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $category = AssetCategory::find($this->input('asset_category_id'));
            if ($category && !$category->is_active) {
                $validator->errors()->add('asset_category_id', 'Kategori asset sudah tidak aktif.');
            }

            $source = ChartOfAccount::find($this->input('acquisition_credit_coa_id'));
            if (!$source) {
                return;
            }

            $type = $this->input('acquisition_type');
            $allowed = match ($type) {
                'CASH' => [['ASET', 'DEBIT']],
                'CREDIT' => [['LIABILITAS', 'KREDIT']],
                'GRANT', 'OPENING_BALANCE' => [['EKUITAS', 'KREDIT']],
                'CORRECTION' => [['EKUITAS', 'KREDIT'], ['PENDAPATAN', 'KREDIT']],
                default => [],
            };
            $mustBeCashBank = $type === 'CASH' ? true : null;

            if (!$allowed || !$source->isUsableFor($allowed, $mustBeCashBank)) {
                $validator->errors()->add(
                    'acquisition_credit_coa_id',
                    'Akun lawan perolehan tidak sesuai jenis perolehan: CASH=Kas/Bank, CREDIT=Hutang, GRANT/OPENING BALANCE=Ekuitas.'
                );
            }
        }];
    }
}
