<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKursPenutupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('controlInventoryFinance') ?? false;
    }

    public function rules(): array
    {
        return [
            'mata_uang' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'periode' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'kurs' => ['required', 'numeric', 'gt:0'],
            'sumber' => ['nullable', 'string', 'max:100'],
        ];
    }
}
