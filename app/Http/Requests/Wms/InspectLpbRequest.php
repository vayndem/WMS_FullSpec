<?php

namespace App\Http\Requests\Wms;

class InspectLpbRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.accepted' => ['required', 'numeric', 'min:0'],
            'decisions.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
