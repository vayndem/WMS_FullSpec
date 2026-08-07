<?php

namespace App\Http\Requests;

class UpdatePembagianGudangRequest extends StorePembagianGudangRequest
{
    public function authorize(): bool
    {
        $m = $this->route('pembagian_gudang');
        return $m && ($this->user()?->can('update', $m) ?? false);
    }
}
