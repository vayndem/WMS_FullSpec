<?php

namespace App\Http\Requests\Wms;

class UpdateInventoryLotBlockRequest extends WarehouseOperationRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['blocked' => $this->boolean('blocked')]);
    }

    public function rules(): array
    {
        return [
            'blocked' => ['required', 'boolean'],
            'block_reason' => ['nullable', 'required_if:blocked,1', 'string', 'max:500'],
        ];
    }
}
