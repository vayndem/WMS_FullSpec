<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerhitunganPajakPenghasilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewFinancialStatements') ?? false;
    }

    public function rules(): array
    {
        return [
            'tahun_pajak' => ['required', 'integer', 'min:2000', 'max:2999'],
            'peredaran_bruto' => ['required', 'numeric', 'gt:0'],
            'posting_date' => ['required', 'date'],
        ];
    }
}
