<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockAccountingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lock = $this->route('period_lock');
        return $lock && ($this->user()?->can('unlock', $lock) ?? false);
    }

    public function rules(): array
    {
        return ['unlock_reason' => ['required', 'string', 'min:5', 'max:1000']];
    }
}
