<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\ChartOfAccount;
use App\Models\Gudang;
use App\Models\InventoryLayer;
use App\Models\InventoryLot;
use App\Models\InventoryReservation;
use App\Models\InvoiceLpb;
use App\Models\LandedCost;
use App\Models\Lpb;
use App\Models\Npk;
use App\Models\PickingOrder;
use App\Models\QualityInspection;
use App\Models\ReplenishmentSuggestion;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WmsControlController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewWmsControl');

        $user = $request->user();
        $warehouseIds = $user->isProduction() ? $user->accessibleGudangIds() : Gudang::pluck('id')->all();
        $mayControlFinance = $user->can('controlInventoryFinance');
        $mayMatchInvoice = $user->can('matchSupplierInvoice');

        return view('wms_control.index', [
            'locations' => WarehouseLocation::with('gudang')->whereIn('gudang_id', $warehouseIds)->orderBy('code')->limit(100)->get(),
            'lots' => InventoryLot::with('bahan')->latest()->limit(100)->get(),
            'reservations' => InventoryReservation::with(['gudang', 'bahan'])->whereIn('gudang_id', $warehouseIds)->latest()->limit(100)->get(),
            'picks' => PickingOrder::with('lines')->whereIn('gudang_id', $warehouseIds)->latest()->limit(50)->get(),
            'inspections' => QualityInspection::with('lpb')->latest()->limit(50)->get(),
            'suggestions' => ReplenishmentSuggestion::whereIn('gudang_id', $warehouseIds)->where('status', 'OPEN')->orderByRaw("FIELD(priority, 'CRITICAL', 'HIGH', 'NORMAL')")->limit(100)->get(),
            'landedCosts' => $mayControlFinance ? LandedCost::latest()->limit(50)->get() : collect(),
            'layers' => $mayControlFinance ? InventoryLayer::with(['bahan', 'gudang'])->where('remaining_quantity', '>', 0)->latest()->limit(200)->get() : collect(),
            'gudangs' => Gudang::whereIn('id', $warehouseIds)->where('aktif', true)->orderBy('nama')->get(),
            'bahans' => Bahan::orderBy('nama')->get(),
            'pendingLpbs' => Lpb::with('details.bahan')->whereIn('gudang_id', $warehouseIds)->whereIn('status', [Lpb::DRAFT, Lpb::POSTED])->where(fn ($query) => $query->whereNull('document_type')->orWhere('document_type', '!=', 'SERVICE_BAP'))->where('receiving_status', '!=', 'PUTAWAY')->latest()->limit(30)->get(),
            'invoices' => $mayMatchInvoice ? InvoiceLpb::where('status', '!=', InvoiceLpb::VOID)->latest()->limit(30)->get() : collect(),
            'creditAccounts' => $mayControlFinance ? ChartOfAccount::where('is_active', true)->where('is_postable', true)->whereIn('kategori_akun', ['LIABILITAS', 'ASET'])->orderBy('kode_akun')->get() : collect(),
            'reversibleLpbs' => $mayControlFinance ? Lpb::where('status', Lpb::POSTED)->where(fn ($query) => $query->whereNull('document_type')->orWhere('document_type', '!=', 'SERVICE_BAP'))->whereDoesntHave('invoiceReceipts')->latest()->limit(20)->get() : collect(),
            'reversibleNpks' => $mayControlFinance ? Npk::where('status', Npk::POSTED)->latest()->limit(20)->get() : collect(),
        ]);
    }
}
