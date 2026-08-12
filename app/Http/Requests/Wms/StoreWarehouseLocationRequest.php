<?php

namespace App\Http\Requests\Wms;

class StoreWarehouseLocationRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'gudang_id' => ['required', 'integer', 'exists:gudangs,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:80'],
            'aisle' => ['nullable', 'string', 'max:50'],
            'rack' => ['nullable', 'string', 'max:50'],
            'bin' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:RECEIVING,QC,STORAGE,PICKING,TRANSIT,DAMAGED'],
            'capacity' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
