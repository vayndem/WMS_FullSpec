<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wms\InventoryFinancialActionRequest;
use App\Http\Requests\Wms\MatchInvoiceRequest;
use App\Http\Requests\Wms\ReverseInventoryDocumentRequest;
use App\Http\Requests\Wms\StoreLandedCostRequest;
use App\Models\InvoiceLpb;
use App\Models\LandedCost;
use App\Models\Lpb;
use App\Models\Npk;
use App\Services\DocumentNumberService;
use App\Services\InventoryReversalService;
use App\Services\LandedCostService;
use App\Services\ThreeWayMatchService;
use Illuminate\Http\RedirectResponse;

class InventoryFinancialControlController extends Controller
{
    public function matchInvoice(MatchInvoiceRequest $request, InvoiceLpb $invoice, ThreeWayMatchService $service): RedirectResponse
    {
        $service->evaluate(
            $invoice,
            (float) $request->input('price_tolerance', 0.5),
            (float) $request->input('quantity_tolerance', 0)
        );

        return back()->with('success', 'Three-way matching diperbarui.');
    }

    public function storeLandedCost(StoreLandedCostRequest $request, LandedCostService $service, DocumentNumberService $numbers): RedirectResponse
    {
        $data = $request->validated();
        $cost = LandedCost::create([
            'number' => $numbers->internal('LDC', 'INV'),
            'date' => $data['date'],
            'description' => $data['description'],
            'allocation_basis' => $data['allocation_basis'],
            'total_amount' => $data['total_amount'],
            'credit_coa_id' => $data['credit_coa_id'],
            'created_by' => $request->user()->id,
        ]);
        $service->allocate($cost, $data['layer_ids']);

        return back()->with('success', 'Draft landed cost dan alokasinya dibuat.');
    }

    public function postLandedCost(InventoryFinancialActionRequest $request, LandedCost $landedCost, LandedCostService $service): RedirectResponse
    {
        $service->post($landedCost);

        return back()->with('success', 'Landed cost diposting ke layer dan GL.');
    }

    public function reverseLpb(ReverseInventoryDocumentRequest $request, Lpb $lpb, InventoryReversalService $service): RedirectResponse
    {
        $service->reverseLpb($lpb, $request->validated('reason'));

        return back()->with('success', 'LPB dan jurnal berhasil dibalik.');
    }

    public function reverseNpk(ReverseInventoryDocumentRequest $request, Npk $npk, InventoryReversalService $service): RedirectResponse
    {
        $service->reverseNpk($npk, $request->validated('reason'));

        return back()->with('success', 'NPK, FIFO, stok, dan jurnal berhasil dibalik.');
    }
}
