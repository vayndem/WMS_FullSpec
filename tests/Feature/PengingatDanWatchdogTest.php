<?php

namespace Tests\Feature;

use App\Models\CrossDock;
use App\Models\DataPesanan;
use App\Models\FakturPenjualan;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\InvarianMenyimpang;
use App\Services\AccountingReconciliationService;
use App\Services\DashboardService;
use App\Services\PengeluaranBarangService;
use App\Services\PengingatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PengingatDanWatchdogTest extends TestCase
{
    use DatabaseTransactions;

    private function pengingat(): PengingatService
    {
        return app(PengingatService::class);
    }

    public function test_a_sales_invoice_falling_due_reaches_the_receivable_reminder(): void
    {
        $faktur = FakturPenjualan::whereIn('status', [FakturPenjualan::POSTED, FakturPenjualan::PARTIALLY_PAID])
            ->where('sisa_tagihan', '>', 0)->firstOrFail();

        $faktur->forceFill(['jatuh_tempo' => today()->subDays(5)])->save();

        $items = $this->pengingat()->piutangJatuhTempo(50);
        $baris = $items->first(fn ($row) => str_contains($row['label'], $faktur->nomor));

        $this->assertNotNull(
            $baris,
            'Sisi jual sudah hidup sejak 2026-09-16 tetapi tidak punya satu pun pengingat; piutang jatuh tempo adalah cermin invoiceJatuhTempo() di sisi beli.'
        );

        $this->assertSame(-5, $baris['hari'], 'Piutang yang lewat lima hari harus terbaca minus lima.');
        $this->assertSame('Piutang jatuh tempo', $baris['konteks']);
    }

    public function test_a_paid_or_void_sales_invoice_never_enters_the_receivable_reminder(): void
    {
        $faktur = FakturPenjualan::whereIn('status', [FakturPenjualan::POSTED, FakturPenjualan::PARTIALLY_PAID])
            ->where('sisa_tagihan', '>', 0)->firstOrFail();

        $faktur->forceFill(['jatuh_tempo' => today()->subDays(5), 'status' => FakturPenjualan::VOID])->save();

        $this->assertNull(
            $this->pengingat()->piutangJatuhTempo(50)->first(fn ($row) => str_contains($row['label'], $faktur->nomor)),
            'Faktur VOID tidak lagi menagih apa pun, jadi tidak boleh muncul sebagai piutang jatuh tempo.'
        );
    }

    public function test_a_cross_dock_mark_that_holds_stock_too_long_is_reported(): void
    {
        $tanda = CrossDock::where('status', CrossDock::DIRESERVASI)->first();

        if (!$tanda) {
            $this->markTestSkipped('Data demo tidak memuat cross dock berstatus DIRESERVASI.');
        }

        $tanda->forceFill(['tanggal' => today()->subDays(PengingatService::AMBANG_CROSS_DOCK_BASI_HARI + 1)])->save();

        $baris = $this->pengingat()->crossDockBasi(null, 50)
            ->first(fn ($row) => str_contains($row['label'], $tanda->nomor));

        $this->assertNotNull(
            $baris,
            'HARI_TERAKHIR hanya membatasi query saran; sebuah mark DIRESERVASI menahan reservasi tanpa batas waktu dan tidak ada yang memberitahu siapa pun.'
        );

        $tanda->forceFill(['tanggal' => today()])->save();

        $this->assertNull(
            $this->pengingat()->crossDockBasi(null, 50)->first(fn ($row) => str_contains($row['label'], $tanda->nomor)),
            'Mark yang baru dibuat hari ini belum basi dan tidak boleh ikut dilaporkan.'
        );
    }

    public function test_a_work_order_with_no_movement_is_reported_as_stalled(): void
    {
        $pesanan = DataPesanan::firstOrFail();
        $pesanan->forceFill(['status' => DataPesanan::DIRILIS])->save();

        $lama = today()->subDays(PengingatService::AMBANG_PERINTAH_KERJA_MANDEK_HARI + 3);

        $pesanan->forceFill(['tanggal' => $lama])->save();
        $pesanan->biaya()->update(['tanggal' => $lama]);

        $this->assertNotNull(
            $this->pengingat()->perintahKerjaMandek(50)->first(fn ($row) => str_contains($row['label'], $pesanan->nomor)),
            'Perintah kerja yang diam berminggu-minggu menahan Barang Dalam Proses tanpa ada yang melihatnya.'
        );

        $pesanan->biaya()->update(['tanggal' => today()]);

        $this->assertNull(
            $this->pengingat()->perintahKerjaMandek(50)->first(fn ($row) => str_contains($row['label'], $pesanan->nomor)),
            'Biaya yang baru masuk hari ini membuktikan pekerjaan berjalan, jadi tidak lagi mandek.'
        );
    }

    public function test_an_overdue_gate_pass_is_reused_from_its_own_service_not_requeried(): void
    {
        Auth::setUser(User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'is_active' => true]));

        $keluar = app(PengeluaranBarangService::class)->buat([
            'tanggal' => today()->subDays(20)->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'PERBAIKAN',
            'estimasi_kembali' => today()->subDays(4)->toDateString(),
            'items' => [['deskripsi' => 'Mesin uji pengingat', 'jumlah' => 1]],
        ]);

        app(PengeluaranBarangService::class)->kirim($keluar);

        $this->assertNotNull(
            $this->pengingat()->barangKeluarBelumKembali(50)->first(fn ($row) => str_contains($row['label'], $keluar->nomor)),
            'PengeluaranBarangService::terlambat() sudah menghitung ini, tetapi hasilnya hanya muncul kalau ada yang membuka halamannya.'
        );
    }

    public function test_the_daily_digest_covers_the_sell_side_and_not_only_the_buy_side(): void
    {
        Notification::fake();

        $this->artisan('wms:pengingat-harian')
            ->expectsOutputToContain('Piutang jatuh tempo')
            ->expectsOutputToContain('Cross dock menahan stok')
            ->expectsOutputToContain('Perintah kerja mandek')
            ->expectsOutputToContain('Barang keluar belum kembali')
            ->assertSuccessful();
    }

    public function test_the_watchdog_passes_while_every_invariant_holds(): void
    {
        $this->artisan('wms:periksa-invarian')->assertSuccessful();

        $this->assertSame(
            0,
            app(AccountingReconciliationService::class)->checks()->where('invalid', '>', 0)->count(),
            'Watchdog hanya boleh lulus ketika kedelapan invarian benar-benar valid.'
        );
    }

    public function test_the_watchdog_fails_and_notifies_when_an_invariant_breaks(): void
    {
        Notification::fake();

        $opname = StockOpname::first();
        $this->assertNotNull($opname, 'Data demo harus punya stock opname untuk merusak invarian stok.');

        \App\Models\Bahan::query()->limit(1)->update(['stok_onhand' => 999999]);

        $this->artisan('wms:periksa-invarian')->assertFailed();

        Notification::assertSentTo(
            User::aktif()->berperan([User::ROLE_ACCOUNTING])->get(),
            \App\Notifications\InvarianMenyimpang::class
        );
    }

    public function test_a_stock_opname_can_be_exported_to_excel_not_only_printed(): void
    {
        $opname = StockOpname::first();
        $this->assertNotNull($opname);

        $this->actingAs(User::factory()->create(['type' => User::ROLE_SUPER_ADMIN, 'is_active' => true]))
            ->get(route('stock-opname.excel', $opname))
            ->assertOk()
            ->assertDownload('stock-opname-' . $opname->number . '.xlsx');
    }

    public function test_the_finance_dashboard_panel_sees_receivables_not_only_payables(): void
    {
        $faktur = FakturPenjualan::whereIn('status', [FakturPenjualan::POSTED, FakturPenjualan::PARTIALLY_PAID])
            ->where('sisa_tagihan', '>', 0)->firstOrFail();

        $faktur->forceFill(['jatuh_tempo' => today()->subDays(3)])->save();

        $panel = app(DashboardService::class)->untuk(
            User::factory()->create(['type' => User::ROLE_FINANCE, 'is_active' => true])
        );

        $this->assertNotNull(
            collect($panel['data']['reminders'])->first(fn ($row) => $row['konteks'] === 'Piutang jatuh tempo'),
            'PengingatService melayani panel dashboard dan digest sekaligus; menambah pengingat hanya ke digest akan membuat panelnya tetap buta.'
        );
    }

    public function test_an_invariant_notification_renders_without_pretending_to_be_a_deadline(): void
    {
        $akuntansi = User::aktif()->berperan([User::ROLE_ACCOUNTING])->firstOrFail();

        $akuntansi->notify(new InvarianMenyimpang([
            ['key' => 'stock', 'label' => 'Stok on hand vs layer', 'total' => 5, 'invalid' => 2],
        ]));

        $data = $akuntansi->notifications()->latest()->firstOrFail()->data;

        $this->assertSame('INVARIAN', $data['jenis']);
        $this->assertSame('error', $data['warna']);
        $this->assertArrayNotHasKey(
            'hari',
            $data['rincian'][0],
            'Sebuah invarian yang menyimpang bukan tenggat; baris rinciannya tidak boleh mengaku punya sisa hari.'
        );

        $this->actingAs($akuntansi)->get(route('notifikasi.index'))
            ->assertOk()
            ->assertSee('2 dari 5 baris menyimpang');
    }
}
