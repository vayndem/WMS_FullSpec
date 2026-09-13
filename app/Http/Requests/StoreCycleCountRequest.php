<?php

namespace App\Http\Requests;

use App\Models\StockOpname;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCycleCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('create', StockOpname::class) ?? false)
            && $this->user()->canAccessGudang((int) $this->input('warehouse_id'), 'opname');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|integer|exists:gudangs,id',
            'kelas_abc' => ['required', Rule::in(['A', 'B', 'C'])],
            'cutoff_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
