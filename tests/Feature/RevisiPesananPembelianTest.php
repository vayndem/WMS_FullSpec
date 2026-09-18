<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevisiPesananPembelianTest extends TestCase
{
    use DatabaseTransactions;

    private function purchasing(): User
    {
        return User::factory()->create(['type' => User::ROLE_PURCHASING, 'is_active' => true]);
    }

    private function buatPo(User $user, Bahan $bahan, Gudang $gudang, Supplier $supplier): PesananPembelian
    {
        $noPo = $this->actingAs($user)->postJson(route('pembelian.store'), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 10]],
        ])->assertCreated()->json('data.no_po');

        return PesananPembelian::where('no_po', $noPo)->with('details')->firstOrFail();
    }

    public function test_revising_a_printed_purchase_order_archives_it_into_the_history_tables(): void
    {
        $user = $this->purchasing();
        $gudang = Gudang::where('jenis', Gudang::NORMAL)->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $supplier = Supplier::create([
            'nama' => 'Supplier Revisi',
            'alamat' => 'Jl. Revisi',
            'telp' => '08000000' . random_int(10, 99),
            'pembayaran' => 'Transfer',
        ]);

        $po = $this->buatPo($user, $bahan, $gudang, $supplier);

        $po->forceFill(['cetak' => 1, 'counter_asli' => 1])->save();

        $noRevisi = $po->no_po . '-1';

        $this->assertSame(0, DB::table('wms_riwayat_pesanan_pembelian')->where('no_revisi', $noRevisi)->count());

        $this->actingAs($user)->putJson(route('pembelian.update', $po->no_po), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'notes' => 'Harga dinaikkan setelah PO tercetak.',
            'details' => [['bahan_id' => $bahan->id, 'harga' => 7500, 'jumlah' => 10]],
        ])->assertSuccessful();

        $header = DB::table('wms_riwayat_pesanan_pembelian')->where('no_revisi', $noRevisi)->first();

        $this->assertNotNull($header, 'Revisi PO tercetak wajib mengarsipkan header ke wms_riwayat_pesanan_pembelian.');
        $this->assertSame('REVISION', $header->action);
        $this->assertSame($po->no_po, $header->no_po);

        $this->assertGreaterThan(
            0,
            DB::table('wms_riwayat_pesanan_pembelian_detail')->where('no_revisi', $noRevisi)->count(),
            'Baris PO sebelum revisi wajib ikut terarsip.'
        );

        $this->assertSame(7500.0, (float) $po->fresh('details')->details->first()->harga);
    }

    public function test_an_unprinted_purchase_order_is_revised_without_an_archive_row(): void
    {
        $user = $this->purchasing();
        $gudang = Gudang::where('jenis', Gudang::NORMAL)->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $supplier = Supplier::create([
            'nama' => 'Supplier Belum Cetak',
            'alamat' => 'Jl. Draft',
            'telp' => '08000000' . random_int(10, 99),
            'pembayaran' => 'Transfer',
        ]);

        $po = $this->buatPo($user, $bahan, $gudang, $supplier);

        $sebelum = DB::table('wms_riwayat_pesanan_pembelian')->count();

        $this->actingAs($user)->putJson(route('pembelian.update', $po->no_po), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 6000, 'jumlah' => 12]],
        ])->assertSuccessful();

        $this->assertSame(
            $sebelum,
            DB::table('wms_riwayat_pesanan_pembelian')->count(),
            'PO yang belum pernah dicetak tidak perlu diarsipkan.'
        );
    }
}
