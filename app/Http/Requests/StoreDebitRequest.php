<?php

namespace App\Http\Requests;

use App\Models\Debit;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Debit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:100|unique:debits,kode',
            'nama' => 'required|string|max:200',
        ];
    }
}
