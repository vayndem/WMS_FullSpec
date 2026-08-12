<?php

namespace App\Http\Requests\Wms;

use Illuminate\Foundation\Http\FormRequest;

class MatchInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('matchSupplierInvoice') ?? false;
    }

    public function rules(): array
    {
        return [
            'price_tolerance' => ['nullable', 'numeric', 'between:0,100'],
            'quantity_tolerance' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
