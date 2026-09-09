<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_saran_pengisian_ulang', function (Blueprint $table) {
            $table->foreignId('material_request_id')->nullable()->after('status')
                ->constrained('requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wms_saran_pengisian_ulang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_request_id');
        });
    }
};
