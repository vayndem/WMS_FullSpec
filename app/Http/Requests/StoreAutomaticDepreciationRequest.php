<?php

namespace App\Http\Requests;

use App\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;

class StoreAutomaticDepreciationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('depreciateAny', Aset::class) ?? false;
    }

    public function rules(): array
    {
        return ['posting_date' => 'required|date', 'period_label' => 'required|string|max:100'];
    }
}
