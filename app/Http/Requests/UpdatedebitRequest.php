<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDebitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('debit')) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('debits', 'kode')->ignore($this->route('debit')),
            ],
            'nama' => 'required|string|max:200',
        ];
    }
}
