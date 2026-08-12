<?php

namespace App\Http\Requests;

use App\Models\TransferGudang;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveTransferGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transfer = $this->route('transfer_gudang');

        return $transfer instanceof TransferGudang
            && ($this->user()?->can('receive', $transfer) ?? false);
    }

    public function rules(): array
    {
        return [
            'received' => ['nullable', 'array'],
            'received.*' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
