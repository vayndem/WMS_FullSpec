<?php

namespace App\Http\Requests\Wms;

class StoreBiayaTambahanRequest extends InventoryFinancialControlRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'allocation_basis' => ['required', 'in:VALUE,QUANTITY'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'credit_coa_id' => ['required', 'integer', 'exists:wms_bagan_akun,id'],
            'layer_ids' => ['required', 'array', 'min:1'],
            'layer_ids.*' => ['integer', 'distinct', 'exists:wms_layer_persediaan,id'],
        ];
    }
}
