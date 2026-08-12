<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('document_sequences',function(Blueprint $table){
            $table->id();$table->string('namespace',40);$table->string('period_key',20);
            $table->unsignedInteger('last_number')->default(0);$table->timestamps();
            $table->unique(['namespace','period_key']);
        });
        Schema::table('invoice_payments',function(Blueprint $table){
            $table->string('payment_number',30)->nullable()->unique()->after('id');
        });
    }
    public function down(): void {
        Schema::table('invoice_payments',fn(Blueprint $table)=>$table->dropColumn('payment_number'));
        Schema::dropIfExists('document_sequences');
    }
};
