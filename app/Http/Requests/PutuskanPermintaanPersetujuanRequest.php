<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutuskanPermintaanPersetujuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_checker' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'catatan_checker' => 'catatan',
        ];
    }
}
