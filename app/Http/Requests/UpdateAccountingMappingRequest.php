<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ChartOfAccount;

class UpdateAccountingMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateMapping', ChartOfAccount::class) ?? false;
    }

    public function rules(): array
    {
        $account = fn() => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')
            ->where(fn($query) => $query->where('is_active', 1)->where('is_postable', 1))];

        return [
            'global' => 'required|array',
            'global.HUTANG_USAHA' => $account(),
            'global.PPN_MASUKAN' => $account(),
            'global.HUTANG_PPH23' => $account(),
            'global.BIAYA_BANK' => $account(),
            'global.BEBAN_MATERAI' => $account(),
            'global.SELISIH_BAYAR' => $account(),
            'global.BIAYA_ONGKIR' => $account(),
            'global.DISKON_PEMBELIAN' => $account(),
            'categories' => 'required|array',
            'categories.*.coa_persediaan_id' => $account(),
            'categories.*.coa_beban_id' => $account(),
            'categories.*.coa_clearing_lpb_id' => $account(),
            'categories.*.coa_beban_selisih_opname_id' => $account(),
            'categories.*.coa_koreksi_opname_id' => $account(),
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $expected = [
                'HUTANG_USAHA' => ['LIABILITAS', 'KREDIT'],
                'PPN_MASUKAN' => ['ASET', 'DEBIT'],
                'HUTANG_PPH23' => ['LIABILITAS', 'KREDIT'],
                'BIAYA_BANK' => ['BEBAN', 'DEBIT'],
                'BEBAN_MATERAI' => ['BEBAN', 'DEBIT'],
                'SELISIH_BAYAR' => ['PENDAPATAN', 'KREDIT'],
                'BIAYA_ONGKIR' => ['BEBAN', 'DEBIT'],
                'DISKON_PEMBELIAN' => ['BEBAN', 'KREDIT'],
            ];
            foreach ($this->input('global', []) as $key => $id) {
                $coa = ChartOfAccount::find($id);
                if (
                    $coa && isset($expected[$key]) &&
                    ($coa->kategori_akun !== $expected[$key][0] || $coa->posisi_normal !== $expected[$key][1])
                ) {
                    $validator->errors()->add("global.{$key}", "Kategori/posisi normal akun {$key} tidak sesuai.");
                }
            }
            foreach ($this->input('categories', []) as $id => $mapping) {
                foreach (
                    [
                        'coa_persediaan_id' => ['ASET', 'DEBIT'],
                        'coa_beban_id' => ['BEBAN', 'DEBIT'],
                        'coa_clearing_lpb_id' => ['LIABILITAS', 'KREDIT'],
                        'coa_beban_selisih_opname_id' => ['BEBAN', 'DEBIT'],
                        'coa_koreksi_opname_id' => ['PENDAPATAN', 'KREDIT'],
                    ] as $field => $rule
                ) {
                    $coa = ChartOfAccount::find($mapping[$field] ?? null);
                    if ($coa && ($coa->kategori_akun !== $rule[0] || $coa->posisi_normal !== $rule[1])) {
                        $validator->errors()->add("categories.{$id}.{$field}", 'Kategori atau posisi normal akun tidak sesuai dengan perannya.');
                    }
                }
            }
        }];
    }
}
