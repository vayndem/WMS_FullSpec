<?php

namespace App\Http\Requests\Wms;

class StoreInventorySerialRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:inventory_serials,serial_number'],
        ];
    }
}
