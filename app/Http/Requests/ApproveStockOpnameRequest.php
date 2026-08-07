<?php

namespace App\Http\Requests;

use App\Models\StockOpname;
use Illuminate\Foundation\Http\FormRequest;

class ApproveStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opname = $this->route('stock_opname');
        return $opname instanceof StockOpname && ($this->user()?->can('approve', $opname) ?? false);
    }

    public function rules(): array
    {
        $rules = [
            'approval_note' => 'nullable|string|max:2000',
        ];
        if ($this->routeIs('stock-opname.approve')) {
            $rules += [
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer|exists:stock_opname_details,id',
                'items.*.unit_cost' => 'nullable|numeric|min:0|max:99999999999999',
            ];
        }
        return $rules;
    }
}
