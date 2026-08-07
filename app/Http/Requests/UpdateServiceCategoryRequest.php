<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('service_category'));
    }
    public function rules(): array
    {
        $available = Rule::exists('chart_of_accounts', 'id')
            ->where(fn ($query) => $query->where('is_active', 1)->where('is_postable', 1));

        return [
            'expense_coa_id' => ['required', 'integer', $available],
            'grni_coa_id' => ['required', 'integer', $available],
            'is_active' => 'nullable|boolean',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            /** @var ServiceCategory|null $category */
            $category = $this->route('service_category');
            $expense = ChartOfAccount::find($this->input('expense_coa_id'));
            $grni = ChartOfAccount::find($this->input('grni_coa_id'));
            $expensePairs = $category?->code === ServiceCategory::PRODUCTION
                ? [['ASET', 'DEBIT']]
                : [['BEBAN', 'DEBIT']];

            if ($expense && !$expense->isUsableFor($expensePairs)) {
                $validator->errors()->add(
                    'expense_coa_id',
                    $category?->code === ServiceCategory::PRODUCTION
                        ? 'Jasa produksi wajib memakai akun WIP kategori ASET dengan posisi normal DEBIT.'
                        : 'Jasa operasional wajib memakai akun BEBAN dengan posisi normal DEBIT.'
                );
            }
            if ($grni && !$grni->isUsableFor([['LIABILITAS', 'KREDIT']])) {
                $validator->errors()->add('grni_coa_id', 'Akun GRNI jasa wajib LIABILITAS dengan posisi normal KREDIT.');
            }
        }];
    }
}
