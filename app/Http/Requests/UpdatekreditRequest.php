<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('kredit')) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kredits', 'kode')->ignore($this->route('kredit')),
            ],
            'nama' => 'required|string|max:200',
        ];
    }
}
