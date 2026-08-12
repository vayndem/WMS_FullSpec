<?php

namespace App\Http\Requests\Wms;

class ReverseInventoryDocumentRequest extends InventoryFinancialControlRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
