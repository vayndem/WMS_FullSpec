<?php

namespace App\Http\Requests\Wms;

use Illuminate\Foundation\Http\FormRequest;

abstract class WarehouseOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operateWarehouse') ?? false;
    }
}
