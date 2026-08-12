<?php

namespace App\Http\Requests\Wms;

class StoreInventoryLotRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'bahan_id' => ['required', 'integer', 'exists:bahans,id'],
            'lot_number' => ['required', 'string', 'max:100'],
            'manufactured_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:manufactured_at'],
        ];
    }
}
