<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE requests MODIFY status ENUM('PENDING', 'APPROVED', 'REJECTED', 'FULFILLED') DEFAULT 'PENDING'");

        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->after('no_request');

            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn('requested_by');
        });

        DB::statement("ALTER TABLE requests MODIFY status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING'");
    }
};
