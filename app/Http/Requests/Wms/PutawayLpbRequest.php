<?php

namespace App\Http\Requests\Wms;

class PutawayLpbRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return ['warehouse_location_id' => ['required', 'integer', 'exists:warehouse_locations,id']];
    }
}
