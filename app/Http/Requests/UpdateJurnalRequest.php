<?php

namespace App\Http\Requests;

use App\Models\Jurnal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJurnalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $jurnal = $this->route('jurnal') ?? $this->route('id');
        if (is_numeric($jurnal)) {
            $jurnal = Jurnal::find($jurnal);
        }

        return $jurnal ? ($this->user()?->can('update', $jurnal) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'no_jurnal'        => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', Rule::unique('jurnals', 'no_jurnal')->ignore(is_object($this->route('jurnal')) ? $this->route('jurnal')->id : $this->route('jurnal'))],
            'tanggal'          => 'required|date',
            'keterangan'       => 'nullable|string',
            'sumber_transaksi' => 'nullable|string|max:100',
            'reff_id'          => 'nullable|integer',
            'details'          => 'required|array|min:2',
            'details.*.coa_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)
            )],
            'details.*.debit'  => 'nullable|numeric|min:0',
            'details.*.kredit' => 'nullable|numeric|min:0',
            'details.*.keterangan' => 'nullable|string|max:500',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $debit = collect($this->input('details', []))->sum(fn($line) => (float) ($line['debit'] ?? 0));
            $credit = collect($this->input('details', []))->sum(fn($line) => (float) ($line['kredit'] ?? 0));
            foreach ($this->input('details', []) as $line) {
                if (((float) ($line['debit'] ?? 0) > 0) === ((float) ($line['kredit'] ?? 0) > 0)) {
                    $validator->errors()->add('details', 'Setiap baris harus memiliki tepat salah satu debit atau kredit.');
                }
            }
            if ($debit <= 0 || abs($debit - $credit) > .01) {
                $validator->errors()->add('details', 'Total debit dan kredit harus seimbang.');
            }
        }];
    }
}
