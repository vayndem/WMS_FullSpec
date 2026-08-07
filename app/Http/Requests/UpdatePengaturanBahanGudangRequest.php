<?php

namespace App\Http\Requests;

class UpdatePengaturanBahanGudangRequest extends StorePengaturanBahanGudangRequest
{
    public function authorize(): bool
    {
        $m = $this->route('pengaturan_bahan_gudang');
        return $m && ($this->user()?->can('update', $m) ?? false);
    }
}
