<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdaterequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('request')) ?? false;
    }

    public function rules(): array
    {
        return [
            'items'              => 'required|array|min:1',
            'items.*.jumlah_acc' => 'required|numeric|min:0',
            'catatan_approver'   => 'nullable|string',
        ];
    }
}
