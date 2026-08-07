<?php

namespace App\Http\Requests;

use App\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $taxRate = $this->route('tax_rate');
        return $taxRate
            ? ($this->user()?->can('update', $taxRate) ?? false)
            : ($this->user()?->can('create', TaxRate::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'tax_type' => ['required', Rule::in(['PPN', 'PPH23'])],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
