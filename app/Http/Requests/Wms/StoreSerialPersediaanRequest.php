<?php

namespace App\Http\Requests\Wms;

class StoreSerialPersediaanRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'inventory_lot_id' => ['required', 'integer', 'exists:wms_lot_persediaan,id'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:wms_serial_persediaan,serial_number'],
        ];
    }
}
