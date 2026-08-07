<?php

namespace App\Http\Requests;

use App\Models\Kredit;
use Illuminate\Foundation\Http\FormRequest;

class StoreKreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Kredit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:100|unique:kredits,kode',
            'nama' => 'required|string|max:200',
        ];
    }
}
