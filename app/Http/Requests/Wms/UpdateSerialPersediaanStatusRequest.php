<?php

namespace App\Http\Requests\Wms;

use Illuminate\Validation\Rule;

class UpdateSerialPersediaanStatusRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['AVAILABLE', 'ISSUED', 'SCRAPPED'])],
        ];
    }
}
