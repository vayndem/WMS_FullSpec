<?php

namespace App\Http\Requests\Wms;

class StoreInventoryReservationRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'gudang_id' => ['required', 'integer', 'exists:gudangs,id'],
            'bahan_id' => ['required', 'integer', 'exists:bahans,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'integer'],
        ];
    }
}
