<?php

namespace App\Http\Requests;

use App\Models\ServiceBap;
use App\Models\ServicePoDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreServiceBapRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        foreach ($items as $index => $item) {
            $detail = ServicePoDetail::find($item['service_po_detail_id'] ?? 0);
            if ($detail) {
                // BAP bukan progress parsial. Seluruh nilai detail PO menjadi
                // snapshot pekerjaan yang dimulai dan baru diakui saat invoice.
                $items[$index]['progress_percent'] = 100;
                $items[$index]['amount'] = (float) $detail->subtotal;
            }
        }
        $this->merge(['items' => $items]);
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceBap::class);
    }
    public function rules(): array
    {
        return [
            'id_lpb' => ['required', 'string', 'max:30', 'regex:/^[A-Z]{3}\d{9}$/', 'unique:lpbs,id_lpb'],
            'tanggal' => 'required|date',
            'no_po' => 'required|exists:pembelians,no_po',
            'no_sj' => 'required|string|max:250',
            'items' => 'required|array|min:1',
            'items.*.service_po_detail_id' => 'required|distinct|exists:service_po_details,id',
            'items.*.progress_percent' => 'required|numeric|in:100',
            'items.*.amount' => 'required|numeric|gt:0',
            'items.*.department_cost_center' => 'nullable|string|max:150',
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.allocations' => 'nullable|array',
            'items.*.allocations.*.datapesanan_code' => 'required|string|max:100',
            'items.*.allocations.*.percentage' => 'required|numeric|gt:0|max:100',
        ];
    }
    public function after(): array
    {
        return [function (Validator $validator) {
            $po = \App\Models\ServicePurchase::with('serviceDetails')
                ->where('no_po', $this->input('no_po'))->first();
            $submittedIds = collect($this->input('items', []))
                ->pluck('service_po_detail_id')->map(fn ($id) => (int) $id)->sort()->values();
            $expectedIds = $po?->serviceDetails->pluck('id')->map(fn ($id) => (int) $id)->sort()->values() ?? collect();
            if (!$po || $submittedIds->all() !== $expectedIds->all()) {
                $validator->errors()->add('items', 'BAP harus mencakup seluruh pekerjaan dalam satu PO Jasa.');
            }
            foreach ($this->input('items', []) as $i => $item) {
                $detail = ServicePoDetail::with('category')->find($item['service_po_detail_id'] ?? 0);
                if (!$detail) continue;
                $expected = round((float)$detail->subtotal, 2);
                if (!$detail->id_kategori) {
                    $validator->errors()->add("items.$i.service_po_detail_id", 'Detail jasa belum mempunyai kategori bahan.');
                }
                if (abs($expected - (float)($item['amount'] ?? 0)) > .01)
                    $validator->errors()->add("items.$i.amount", "Nilai BAP harus sama dengan nilai penuh PO Jasa: {$expected}.");
                if ($detail->category->requires_cost_center && empty($item['department_cost_center']))
                    $validator->errors()->add("items.$i.department_cost_center", 'Departemen/cost center wajib untuk jasa operasional.');
                if ($detail->category->requires_datapesanan) {
                    $alloc = $item['allocations'] ?? [];
                    if (!$alloc) $validator->errors()->add("items.$i.allocations", 'Alokasi Datapesanan wajib untuk jasa produksi.');
                    $sum = collect($alloc)->sum(fn($x) => (float)($x['percentage'] ?? 0));
                    if (abs($sum - 100) > .01) $validator->errors()->add("items.$i.allocations", 'Total alokasi Datapesanan harus 100%.');
                    if (collect($alloc)->pluck('datapesanan_code')->filter()->duplicates()->isNotEmpty())
                        $validator->errors()->add("items.$i.allocations", 'Kode Datapesanan tidak boleh duplikat.');
                }
            }
        }];
    }
}
