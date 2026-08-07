<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTransferGudangRequest extends StoreTransferGudangRequest
{
    public function authorize(): bool
    {
        $m = $this->route('transfer_gudang');
        return $m && ($this->user()?->can('update', $m) ?? false);
    }
    public function rules(): array
    {
        $r = parent::rules();
        $r['nomor_transfer'] = ['required', 'string', 'max:50', Rule::unique('transfer_gudangs', 'nomor_transfer')->ignore($this->route('transfer_gudang'))];
        return $r;
    }
}
