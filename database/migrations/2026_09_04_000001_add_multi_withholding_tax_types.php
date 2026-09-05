<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->string('jenis_pph', 20)->nullable()->after('tarif_pph');
        });

        DB::table('wms_faktur_pembelian')->update(['jenis_pph' => 'PPH23']);

        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->decimal('potongan_pph', 15, 2)->default(0)->after('potongan_pph23');
        });

        DB::table('wms_pembayaran_faktur')->update(['potongan_pph' => DB::raw('potongan_pph23')]);

        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->dropColumn('potongan_pph23');
        });
    }

    public function down(): void
    {
        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->decimal('potongan_pph23', 15, 2)->default(0)->after('potongan_pph');
        });

        DB::table('wms_pembayaran_faktur')->update(['potongan_pph23' => DB::raw('potongan_pph')]);

        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->dropColumn('potongan_pph');
        });

        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->dropColumn('jenis_pph');
        });
    }
};
