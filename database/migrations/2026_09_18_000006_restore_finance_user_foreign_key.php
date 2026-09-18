<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $penggantiId = DB::table('users')->orderBy('id')->value('id');

        DB::table('wms_pembayaran_faktur as p')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'p.finance_user_id'))
            ->update(['finance_user_id' => $penggantiId]);

        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->foreign('finance_user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->dropForeign(['finance_user_id']);
        });
    }
};
