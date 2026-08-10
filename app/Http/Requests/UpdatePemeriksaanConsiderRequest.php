<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePemeriksaanConsiderRequest extends StorePemeriksaanConsiderRequest
{
    public function authorize(): bool
    {
        $m = $this->route('pemeriksaan_consider');
        return $m && ($this->user()?->can('update', $m) ?? false);
    }
    public function rules(): array
    {
        $r = parent::rules();
        $r['nomor_pemeriksaan'] = ['required', 'string', 'max:50', Rule::unique('pemeriksaan_considers', 'nomor_pemeriksaan')->ignore($this->route('pemeriksaan_consider'))];
        return $r;
    }
}
