<?php

namespace App\Http\Requests;

use App\Models\Jurnal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJurnalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Jurnal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'no_jurnal'        => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', 'unique:jurnals,no_jurnal'],
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
            $debit = 0;
            $credit = 0;
            foreach ($this->input('details', []) as $line) {
                $d = (float) ($line['debit'] ?? 0);
                $c = (float) ($line['kredit'] ?? 0);
                if (($d > 0) === ($c > 0)) {
                    $validator->errors()->add('details', 'Setiap baris harus berisi tepat salah satu debit atau kredit.');
                }
                $debit += $d;
                $credit += $c;
            }
            if ($debit <= 0 || abs($debit - $credit) > 0.01) {
                $validator->errors()->add('details', 'Total debit dan kredit jurnal harus seimbang.');
            }
        }];
    }
}
