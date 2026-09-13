<?php

namespace App\Services;

use App\Models\FakturPembelian;
use App\Models\LayerPersediaan;
use App\Models\MaterialRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengingatService
{
    public const AMBANG_KEDALUWARSA_HARI = 30;
    public const AMBANG_JATUH_TEMPO_HARI = 14;
    public const AMBANG_TRANSFER_MENGGANTUNG_HARI = 3;

    public function susun(string $konteks, string $label, mixed $tanggal, string $url, ?float $nilai = null): ?array
    {
        if (!$tanggal) {
            return null;
        }

        $tanggal = Carbon::parse($tanggal)->startOfDay();

        return [
            'konteks' => $konteks,
            'label' => $label,
            'tanggal' => $tanggal,
            'hari' => (int) today()->diffInDays($tanggal, false),
            'url' => $url,
            'nilai' => $nilai,
        ];
    }

    public function urutkan(array $items): Collection
    {
        return collect($items)->filter()->sortBy('hari')->values();
    }

    public function invoiceJatuhTempo(int $limit = 6): Collection
    {
        $rows = FakturPembelian::with('supplier')
            ->whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0)
            ->whereNotNull('tgl_deadline_pembayaran')
            ->whereDate('tgl_deadline_pembayaran', '<=', today()->addDays(self::AMBANG_JATUH_TEMPO_HARI))
            ->orderBy('tgl_deadline_pembayaran')
            ->limit($limit)->get();

        return $this->urutkan($rows->map(fn ($inv) => $this->susun(
            'Invoice jatuh tempo',
            $inv->no_invoice . ' · ' . ($inv->supplier->nama ?? '-'),
            $inv->tgl_deadline_pembayaran,
            route('faktur-pembelian.index'),
            (float) $inv->sisa_tagihan,
        ))->all());
    }

    public function lotSegeraKedaluwarsa(?array $warehouseIds = null, int $limit = 5): Collection
    {
        $rows = LayerPersediaan::with(['lot.bahan', 'gudang'])
            ->where('remaining_quantity', '>', 0)
            ->where('stock_status', 'AVAILABLE')
            ->when($warehouseIds !== null, fn ($query) => $query->whereIn('gudang_id', $warehouseIds))
            ->whereHas('lot', fn ($lot) => $lot->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', today()->addDays(self::AMBANG_KEDALUWARSA_HARI)))
            ->get()
            ->groupBy('inventory_lot_id')
            ->map(fn ($layers) => [
                'lot' => $layers->first()->lot,
                'gudang' => $layers->first()->gudang,
                'sisa' => (float) $layers->sum('remaining_quantity'),
            ])
            ->sortBy(fn ($row) => $row['lot']->expires_at)
            ->take($limit);

        return $this->urutkan($rows->map(fn ($row) => $this->susun(
            'Lot kedaluwarsa',
            ($row['lot']->bahan->nama ?? 'Bahan') . ' · lot ' . $row['lot']->lot_number . ' · ' . ($row['gudang']->nama ?? '-'),
            $row['lot']->expires_at,
            route('wms-control.index'),
            $row['sisa'],
        ))->all());
    }

    public function transferMenggantung(?array $warehouseIds = null, int $limit = 5): Collection
    {
        $rows = DB::table('transfer_gudangs as tg')
            ->leftJoin('gudangs as asal', 'asal.id', '=', 'tg.gudang_asal_id')
            ->leftJoin('gudangs as tujuan', 'tujuan.id', '=', 'tg.gudang_tujuan_id')
            ->where('tg.status', 'DIKIRIM')
            ->when($warehouseIds !== null, fn ($query) => $query->where(fn ($q) => $q
                ->whereIn('tg.gudang_asal_id', $warehouseIds)->orWhereIn('tg.gudang_tujuan_id', $warehouseIds)))
            ->orderBy('tg.dikirim_pada')
            ->limit($limit)
            ->get(['tg.nomor_transfer', 'tg.dikirim_pada', 'tg.tanggal', 'asal.nama as asal_nama', 'tujuan.nama as tujuan_nama']);

        return $this->urutkan($rows->map(fn ($row) => $this->susun(
            'Transfer belum diterima',
            $row->nomor_transfer . ' · ' . ($row->asal_nama ?? '-') . ' → ' . ($row->tujuan_nama ?? '-'),
            $row->dikirim_pada ?: $row->tanggal,
            route('transfer-gudangs.index'),
        ))->all());
    }

    public function transferTerlaluLamaMenggantung(int $limit = 20): Collection
    {
        return $this->transferMenggantung(null, $limit)
            ->filter(fn ($row) => $row['hari'] <= -self::AMBANG_TRANSFER_MENGGANTUNG_HARI)
            ->values();
    }

    public function invoiceMenungguPersetujuan(int $limit = 20): Collection
    {
        $rows = FakturPembelian::with('supplier')
            ->where('status', FakturPembelian::PENDING_APPROVAL)
            ->orderBy('tanggal')
            ->limit($limit)->get();

        return $this->urutkan($rows->map(fn ($inv) => $this->susun(
            'Faktur menunggu persetujuan',
            $inv->no_invoice . ' · ' . ($inv->supplier->nama ?? '-'),
            $inv->tgl_deadline_pembayaran ?: $inv->tanggal,
            route('faktur-pembelian.index'),
            (float) $inv->grand_total,
        ))->all());
    }

    public function requestMenungguPersetujuan(int $limit = 20): Collection
    {
        $rows = MaterialRequest::where('status', MaterialRequest::PENDING)
            ->orderBy('created_at')
            ->limit($limit)->get();

        return $this->urutkan($rows->map(fn ($req) => $this->susun(
            'Request menunggu persetujuan',
            'Request ' . ($req->no_request ?? $req->id),
            $req->created_at,
            route('request.index'),
        ))->all());
    }
}
