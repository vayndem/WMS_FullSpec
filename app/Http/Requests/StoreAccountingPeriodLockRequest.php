<?php

namespace App\Http\Requests;

use App\Models\AccountingPeriodLock;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountingPeriodLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AccountingPeriodLock::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
