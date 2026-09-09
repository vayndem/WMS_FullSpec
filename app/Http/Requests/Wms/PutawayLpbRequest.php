<?php

namespace App\Http\Requests\Wms;

class PutawayLpbRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'locations' => ['required', 'array', 'min:1'],
            'locations.*' => ['required', 'integer', 'exists:wms_lokasi_gudang,id'],
        ];
    }
}
