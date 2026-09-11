<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_asets', function (Blueprint $table) {
            $table->string('kelompok_fiskal', 30)->nullable()->after('depreciation_method')
                ->comment('Kelompok harta UU PPh Pasal 11; null=tidak disusutkan secara fiskal');
            $table->string('metode_penyusutan_fiskal', 30)->nullable()->after('kelompok_fiskal');
            $table->decimal('akumulasi_penyusutan_fiskal', 18, 2)->default(0)->after('metode_penyusutan_fiskal');
        });

        Schema::table('wms_penyusutan_asets', function (Blueprint $table) {
            $table->decimal('amount_fiskal', 18, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('wms_asets', function (Blueprint $table) {
            $table->dropColumn(['kelompok_fiskal', 'metode_penyusutan_fiskal', 'akumulasi_penyusutan_fiskal']);
        });

        Schema::table('wms_penyusutan_asets', fn (Blueprint $table) => $table->dropColumn('amount_fiskal'));
    }
};
