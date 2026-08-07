<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetDepreciationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('depreciate', $this->route('asset'));
    }
    public function rules(): array
    {
        return ['posting_date' => 'required|date', 'period_label' => 'required|string|max:100', 'amount' => 'required|numeric|min:0.01', 'reason' => 'nullable|string|max:1000'];
    }
}
