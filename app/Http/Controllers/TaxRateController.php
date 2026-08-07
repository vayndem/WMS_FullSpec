<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TaxRate::class);
        if ($request->ajax()) {
            return datatables()->of(TaxRate::query()->orderBy('tax_type')->latest('effective_from'))->make(true);
        }
        return view('tax_rate.index');
    }

    public function store(StoreTaxRateRequest $request)
    {
        $this->assertNoOverlap($request->validated());
        $rate = TaxRate::create($request->validated());
        return response()->json(['success' => true, 'message' => 'Tarif pajak berhasil ditambahkan.', 'data' => $rate], 201);
    }

    public function update(StoreTaxRateRequest $request, TaxRate $taxRate)
    {
        $this->assertNoOverlap($request->validated(), $taxRate->id);
        $taxRate->update($request->validated());
        return response()->json(['success' => true, 'message' => 'Tarif pajak berhasil diperbarui.']);
    }

    private function assertNoOverlap(array $data, ?int $exceptId = null): void
    {
        if (!$data['is_active']) return;
        $end = $data['effective_until'] ?? '9999-12-31';
        $overlap = TaxRate::query()->where('tax_type', $data['tax_type'])->where('is_active', true)
            ->when($exceptId, fn($query) => $query->where('id', '!=', $exceptId))
            ->whereDate('effective_from', '<=', $end)
            ->where(fn($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $data['effective_from']))
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['effective_from' => 'Rentang tarif aktif bertabrakan dengan tarif lain untuk jenis pajak yang sama.']);
        }
    }
}
