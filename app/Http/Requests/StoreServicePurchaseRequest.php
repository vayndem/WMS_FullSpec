<?php

namespace App\Http\Requests;

use App\Models\ServiceCategory;
use App\Models\ServicePurchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServicePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('service_purchase') ? 'update' : 'create', $this->route('service_purchase') ?: ServicePurchase::class);
    }
    public function rules(): array
    {
        return [
            'no_po' => ['required', 'string', 'max:30', 'regex:/^\d{2}-\d{2}-[A-Z]{2}-(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)-\d{3}$/', Rule::unique('pembelians', 'no_po')->ignore($this->route('service_purchase'))],
            'tanggal' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'untuk_perhatian' => 'nullable|string|max:250',
            'term' => 'nullable|string|max:250',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.service_category_id' => 'required|exists:service_categories,id',
            'items.*.description' => 'required|string|max:2000',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:30',
            'items.*.unit_price' => 'required|numeric|min:0.01',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $category = ServiceCategory::find($item['service_category_id'] ?? 0);
                if ($category && !$category->kategori_bahan_id) {
                    $validator->errors()->add("items.$index.service_category_id", 'Kategori jasa belum terhubung ke kategori bahan.');
                }
            }
        }];
    }
}
