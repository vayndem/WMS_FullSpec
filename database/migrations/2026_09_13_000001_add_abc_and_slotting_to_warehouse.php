<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_bahan_gudangs', function (Blueprint $table) {
            $table->char('kelas_abc', 1)->nullable()->after('aktif')->index();
            $table->decimal('nilai_pemakaian', 18, 2)->default(0)->after('kelas_abc');
            $table->decimal('kontribusi_kumulatif', 8, 4)->default(0)->after('nilai_pemakaian');
            $table->timestamp('abc_dihitung_pada')->nullable()->after('kontribusi_kumulatif');
        });

        Schema::table('wms_lokasi_gudang', function (Blueprint $table) {
            $table->decimal('panjang_cm', 12, 2)->nullable()->after('capacity');
            $table->decimal('lebar_cm', 12, 2)->nullable()->after('panjang_cm');
            $table->decimal('tinggi_cm', 12, 2)->nullable()->after('lebar_cm');
            $table->decimal('kapasitas_volume_cm3', 18, 2)->nullable()->after('tinggi_cm');
            $table->char('kelas_abc', 1)->nullable()->after('kapasitas_volume_cm3')->index();
            $table->unsignedInteger('urutan_pick')->nullable()->after('kelas_abc');
        });

        Schema::table('bahans', function (Blueprint $table) {
            $table->decimal('volume_cm3', 18, 2)->nullable()->after('satuan_kecil');
        });

        Schema::table('wms_stock_opname', function (Blueprint $table) {
            $table->string('jenis', 20)->default('PENUH')->after('warehouse_id')->index();
            $table->char('kelas_abc', 1)->nullable()->after('jenis');
            $table->string('zona', 60)->nullable()->after('kelas_abc');
        });
    }

    public function down(): void
    {
        Schema::table('wms_stock_opname', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropColumn(['jenis', 'kelas_abc', 'zona']);
        });

        Schema::table('bahans', fn (Blueprint $table) => $table->dropColumn('volume_cm3'));

        Schema::table('wms_lokasi_gudang', function (Blueprint $table) {
            $table->dropIndex(['kelas_abc']);
            $table->dropColumn(['panjang_cm', 'lebar_cm', 'tinggi_cm', 'kapasitas_volume_cm3', 'kelas_abc', 'urutan_pick']);
        });

        Schema::table('pengaturan_bahan_gudangs', function (Blueprint $table) {
            $table->dropIndex(['kelas_abc']);
            $table->dropColumn(['kelas_abc', 'nilai_pemakaian', 'kontribusi_kumulatif', 'abc_dihitung_pada']);
        });
    }
};
