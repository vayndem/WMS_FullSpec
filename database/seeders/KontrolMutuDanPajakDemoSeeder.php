<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\LayerPersediaan;
use App\Models\LotPersediaan;
use App\Models\PemakaianBarang;
use App\Models\PenerimaanBarang;
use App\Models\SerialPersediaan;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\GelombangPengambilanService;
use App\Services\InventoryReversalService;
use App\Services\PajakPenghasilanService;
use App\Services\WarehouseExecutionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class KontrolMutuDanPajakDemoSeeder extends Seeder
{
    public function run(): void
    {
        $gudangUser = User::where('type', User::ROLE_WAREHOUSE)->first();
        $akuntansiUser = User::where('type', User::ROLE_ACCOUNTING)->first();

        if (!$gudangUser || !$akuntansiUser) {
            $this->command?->warn('Seeder kontrol mutu dilewati: pengguna demo belum lengkap.');

            return;
        }

        $this->jalankan('Pemeriksaan kualitas', fn () => $this->pemeriksaanKualitas($gudangUser));
        $this->jalankan('Nomor seri', fn () => $this->nomorSeri());
        $this->jalankan('Gelombang pengambilan', fn () => $this->gelombangPengambilan($gudangUser));
        $this->jalankan('Pembalikan dokumen', fn () => $this->pembalikanDokumen($gudangUser));
        $this->jalankan('Pajak penghasilan badan', fn () => $this->pajakPenghasilan($akuntansiUser));
        $this->jalankan('Saran pengisian ulang', fn () => $this->saranPengisianUlang());
    }

    private function jalankan(string $label, callable $skenario): void
    {
        try {
            $skenario();
        } catch (Throwable $e) {
            $this->command?->warn("Skenario {$label} dilewati: " . $e->getMessage());
        }
    }

    private function pemeriksaanKualitas(User $user): void
    {
        Auth::setUser($user);

        $lpb = PenerimaanBarang::with('details')
            ->where('receiving_status', 'RECEIVED')
            ->whereHas('details')
            ->get()
            ->first(fn (PenerimaanBarang $kandidat) => $kandidat->details->isNotEmpty()
                && $kandidat->details->every(fn ($detail) => LayerPersediaan::where('source_type', 'LPB_DETAIL')
                    ->where('source_id', $detail->id)
                    ->whereColumn('remaining_quantity', 'initial_quantity')
                    ->exists()));

        if (!$lpb) {
            throw new RuntimeException('tidak ada penerimaan yang layernya masih utuh untuk diperiksa.');
        }

        app(WarehouseExecutionService::class)->inspect($lpb, []);

        $this->command?->info("Pemeriksaan kualitas: {$lpb->id_lpb} diperiksa dan seluruh barisnya diterima.");
    }

    private function nomorSeri(): void
    {
        $lot = LotPersediaan::whereHas('layers', fn ($q) => $q->where('remaining_quantity', '>', 0))->first();

        if (!$lot) {
            throw new RuntimeException('belum ada lot berstok untuk didaftarkan nomor serinya.');
        }

        foreach (range(1, 3) as $urut) {
            SerialPersediaan::updateOrCreate(
                ['inventory_lot_id' => $lot->id, 'serial_number' => sprintf('%s-SN%03d', $lot->lot_number, $urut)],
                ['status' => $urut === 3 ? 'BLOCKED' : 'AVAILABLE']
            );
        }

        $this->command?->info("Nomor seri: tiga unit lot {$lot->lot_number} didaftarkan, satu di antaranya diblokir.");
    }

    private function gelombangPengambilan(User $user): void
    {
        Auth::setUser($user);

        $gudang = Gudang::where('kode', 'GDG-UTAMA')->firstOrFail();

        $stok = StokGudang::where('gudang_id', $gudang->id)
            ->whereRaw('stok_tersedia - stok_direservasi >= 2')
            ->orderByDesc('stok_tersedia')
            ->get();

        if ($stok->count() < 2) {
            throw new RuntimeException('stok bebas tidak cukup untuk membentuk gelombang.');
        }

        $eksekusi = app(WarehouseExecutionService::class);
        $gelombangService = app(GelombangPengambilanService::class);

        $pickIds = $stok->take(2)->map(function ($baris) use ($eksekusi, $gudang, $user) {
            $reservasi = $eksekusi->reserve($gudang->id, $baris->bahan_id, 1, 'DEMO_GELOMBANG', null, $user->id);

            return $eksekusi->createPick($reservasi)->id;
        })->all();

        $gelombang = $gelombangService->buat($gudang->id, $pickIds, 'LOKASI', 'Gelombang pagi untuk pengiriman hari ini.');
        $gelombangService->rilis($gelombang, $user);

        $this->command?->info('Gelombang pengambilan: dua pesanan pengambilan digabung dan dirilis ke petugas.');
    }

    private function pembalikanDokumen(User $user): void
    {
        Auth::setUser($user);

        $npk = PemakaianBarang::where('status', 'POSTED')
            ->whereNull('data_pesanan_id')
            ->orderByDesc('id')
            ->first();

        if (!$npk) {
            throw new RuntimeException('tidak ada NPK terposting tanpa perintah kerja yang aman dibalik.');
        }

        app(InventoryReversalService::class)->reverseNpk($npk, 'Salah gudang saat pencatatan, dikoreksi lewat pembalikan terkendali.');

        $this->command?->info("Pembalikan dokumen: NPK {$npk->kode} dibalik beserta jurnal dan stoknya.");
    }

    private function pajakPenghasilan(User $user): void
    {
        Auth::setUser($user);

        $tahun = (int) now()->subYear()->format('Y');

        app(PajakPenghasilanService::class)->posting($tahun, 18000000000, now()->toDateString());

        $this->command?->info("Pajak penghasilan: perhitungan PPh Badan tahun {$tahun} diposting.");
    }

    private function saranPengisianUlang(): void
    {
        Artisan::call('wms:hitung-replenishment');

        $this->command?->info('Saran pengisian ulang: perhitungan titik pesan ulang dijalankan.');
    }
}
