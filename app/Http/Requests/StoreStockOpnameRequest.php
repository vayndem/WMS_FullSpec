<?php

namespace App\Http\Requests;

use App\Models\StockOpname;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockOpname::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}-[A-Z]{2,3}-\d{7}$/', 'unique:stock_opnames,number'],
            'warehouse_id' => 'required|integer|exists:gudangs,id',
            'cutoff_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.bahan_id' => 'required|integer|distinct|exists:bahan,id',
            'items.*.physical_quantity' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:150',
            'items.*.notes' => 'nullable|string|max:1000',
        ];
    }
}
