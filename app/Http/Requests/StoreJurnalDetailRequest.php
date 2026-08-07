<?php

namespace App\Http\Requests;

use App\Models\Jurnal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreJurnalDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $jurnal = Jurnal::find($this->input('jurnal_id'));
        return $jurnal ? ($this->user()?->can('update', $jurnal) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'debit'  => $this->debit ?? 0,
            'kredit' => $this->kredit ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'jurnal_id'  => 'required|integer|exists:jurnals,id',
            'coa_id'     => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where(
                fn($query) => $query->where('is_active', 1)->where('is_postable', 1)
            )],
            'debit'      => 'nullable|numeric|min:0',
            'kredit'     => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $debit = (float) $this->input('debit', 0);
            $credit = (float) $this->input('kredit', 0);
            if (($debit > 0) === ($credit > 0)) {
                $validator->errors()->add('debit', 'Isi tepat salah satu sisi debit atau kredit.');
            }
        }];
    }
}
