<?php

namespace App\Http\Requests;

use App\Models\Bom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBomStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bom = $this->route('bom');

        return $bom instanceof Bom && ($this->user()?->can('update', $bom) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([Bom::AKTIF, Bom::NONAKTIF])],
        ];
    }
}
