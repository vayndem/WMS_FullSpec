<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('admin_namagudang', 'gudangs');

        Schema::table('gudangs', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->unique()->after('id');
            $table->enum('jenis', ['NORMAL', 'CONSIDER', 'RUSAK'])->default('NORMAL')->after('nama')->index();
            $table->text('alamat')->nullable()->after('jenis');
            $table->boolean('aktif')->default(true)->after('alamat');
            $table->boolean('boleh_penerimaan')->default(true)->after('aktif');
            $table->boolean('boleh_npk')->default(true)->after('boleh_penerimaan');
            $table->boolean('boleh_transfer')->default(true)->after('boleh_npk');
            $table->boolean('boleh_opname')->default(true)->after('boleh_transfer');
        });

        foreach (DB::table('gudangs')->orderBy('id')->get() as $gudang) {
            DB::table('gudangs')->where('id', $gudang->id)->update([
                'kode' => 'GDG-' . str_pad((string) $gudang->id, 3, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->foreignId('gudang_id')->nullable()->after('supplier_id')->constrained('gudangs')->restrictOnDelete();
        });
        Schema::table('lpbs', function (Blueprint $table) {
            $table->foreignId('gudang_id')->nullable()->after('no_po')->constrained('gudangs')->restrictOnDelete();
        });

        Schema::create('stok_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $table->decimal('stok_tersedia', 18, 6)->default(0);
            $table->decimal('stok_direservasi', 18, 6)->default(0);
            $table->decimal('stok_dipesan', 18, 6)->default(0);
            $table->timestamps();
            $table->unique(['gudang_id', 'bahan_id']);
        });

        Schema::create('pembagian_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->boolean('boleh_menerima')->default(true);
            $table->boolean('boleh_npk')->default(true);
            $table->boolean('boleh_transfer')->default(true);
            $table->boolean('boleh_opname')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'gudang_id']);
        });

        Schema::create('pengaturan_bahan_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->cascadeOnDelete();
            $table->decimal('stok_minimum', 18, 6)->default(0);
            $table->decimal('stok_maksimum', 18, 6)->default(0);
            $table->decimal('stok_pengaman', 18, 6)->default(0);
            $table->decimal('titik_pemesanan', 18, 6)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['gudang_id', 'bahan_id'], 'pengaturan_bahan_gudang_unique');
        });

        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_mutasi', 50)->unique();
            $table->dateTime('tanggal');
            $table->string('jenis_mutasi', 40)->index();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $table->decimal('jumlah_masuk', 18, 6)->default(0);
            $table->decimal('jumlah_keluar', 18, 6)->default(0);
            $table->decimal('saldo_sebelum', 18, 6);
            $table->decimal('saldo_setelah', 18, 6);
            $table->decimal('harga_satuan', 18, 4)->default(0);
            $table->decimal('total_nilai', 18, 2)->default(0);
            $table->string('jenis_referensi', 60);
            $table->unsignedBigInteger('referensi_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['gudang_id', 'bahan_id', 'tanggal']);
            $table->index(['jenis_referensi', 'referensi_id']);
        });

        Schema::create('transfer_gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transfer', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('gudang_asal_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('gudang_tujuan_id')->constrained('gudangs')->restrictOnDelete();
            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DIKONFIRMASI', 'DIBATALKAN'])->default('DRAFT')->index();
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('diajukan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikonfirmasi_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_transfer_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_gudang_id')->constrained('transfer_gudangs')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['transfer_gudang_id', 'bahan_id'], 'detail_transfer_bahan_unique');
        });

        Schema::create('alokasi_transfer_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_transfer_gudang_id')->constrained('detail_transfer_gudangs')->cascadeOnDelete();
            $table->foreignId('inventory_layer_asal_id')->constrained('inventory_layers')->restrictOnDelete();
            $table->foreignId('inventory_layer_tujuan_id')->nullable()->constrained('inventory_layers')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('harga_satuan', 18, 4);
            $table->decimal('total_nilai', 18, 2);
            $table->timestamps();
        });

        Schema::create('pemeriksaan_considers', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pemeriksaan', 50)->unique();
            $table->date('tanggal');
            $table->foreignId('gudang_consider_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('gudang_baik_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('gudang_rusak_id')->constrained('gudangs')->restrictOnDelete();
            $table->enum('status', ['DRAFT', 'DIKONFIRMASI'])->default('DRAFT')->index();
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikonfirmasi_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_pemeriksaan_considers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_consider_id')->constrained('pemeriksaan_considers')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $table->decimal('jumlah_diperiksa', 18, 6);
            $table->decimal('jumlah_baik', 18, 6);
            $table->decimal('jumlah_rusak', 18, 6);
            $table->text('alasan')->nullable();
            $table->timestamps();
            $table->unique(['pemeriksaan_consider_id', 'bahan_id'], 'detail_consider_bahan_unique');
        });

        foreach (DB::table('lpbs')->leftJoin('lpbdetails', 'lpbdetails.id_lpb', '=', 'lpbs.id_lpb')
            ->leftJoin('bahan', 'bahan.id', '=', 'lpbdetails.id_bahan')
            ->whereNull('lpbs.gudang_id')->select('lpbs.id', 'bahan.tipe_gudang')->distinct()->get() as $receipt) {
            if ($receipt->tipe_gudang) DB::table('lpbs')->where('id', $receipt->id)->update(['gudang_id' => $receipt->tipe_gudang]);
        }
        foreach (DB::table('pembelians')->leftJoin('pembelian_details', 'pembelian_details.no_po', '=', 'pembelians.no_po')
            ->leftJoin('bahan', 'bahan.id', '=', 'pembelian_details.bahan_id')
            ->whereNull('pembelians.gudang_id')->select('pembelians.id', 'bahan.tipe_gudang')->distinct()->get() as $order) {
            if ($order->tipe_gudang) DB::table('pembelians')->where('id', $order->id)->update(['gudang_id' => $order->tipe_gudang]);
        }

        $defaultGudang = DB::table('gudangs')->orderBy('id')->value('id');
        if ($defaultGudang) {
            DB::table('pembelians')->whereNull('gudang_id')->update(['gudang_id' => $defaultGudang]);
            DB::table('lpbs')->whereNull('gudang_id')->update(['gudang_id' => $defaultGudang]);
        }

        DB::table('stok_gudangs')->insertUsing(
            ['gudang_id', 'bahan_id', 'stok_tersedia', 'stok_direservasi', 'stok_dipesan', 'created_at', 'updated_at'],
            DB::table('bahan')->select([
                'tipe_gudang',
                'id',
                'stok_onhand',
                DB::raw('0'),
                'stok_onpurchase',
                DB::raw('CURRENT_TIMESTAMP'),
                DB::raw('CURRENT_TIMESTAMP'),
            ])
        );

        foreach (DB::table('stok_gudangs')->where('stok_tersedia', '>', 0)->get() as $stock) {
            $value = (float) DB::table('inventory_layers')->where('gudang_id', $stock->gudang_id)
                ->where('bahan_id', $stock->bahan_id)->selectRaw('COALESCE(SUM(remaining_quantity * unit_cost), 0) total')->value('total');
            $unitCost = (float) $stock->stok_tersedia > 0 ? $value / (float) $stock->stok_tersedia : 0;
            DB::table('mutasi_stoks')->insert([
                'nomor_mutasi' => 'MIG-STK-' . $stock->id,
                'tanggal' => now(), 'jenis_mutasi' => 'SALDO_AWAL', 'gudang_id' => $stock->gudang_id,
                'bahan_id' => $stock->bahan_id, 'jumlah_masuk' => $stock->stok_tersedia, 'jumlah_keluar' => 0,
                'saldo_sebelum' => 0, 'saldo_setelah' => $stock->stok_tersedia, 'harga_satuan' => $unitCost,
                'total_nilai' => $value, 'jenis_referensi' => 'MIGRASI_MULTI_GUDANG', 'referensi_id' => $stock->id,
                'user_id' => null, 'keterangan' => 'Saldo awal dari migrasi stok existing', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pemeriksaan_considers');
        Schema::dropIfExists('pemeriksaan_considers');
        Schema::dropIfExists('alokasi_transfer_gudangs');
        Schema::dropIfExists('detail_transfer_gudangs');
        Schema::dropIfExists('transfer_gudangs');
        Schema::dropIfExists('mutasi_stoks');
        Schema::dropIfExists('pengaturan_bahan_gudangs');
        Schema::dropIfExists('pembagian_gudangs');
        Schema::dropIfExists('stok_gudangs');
        Schema::table('lpbs', fn(Blueprint $table) => $table->dropConstrainedForeignId('gudang_id'));
        Schema::table('pembelians', fn(Blueprint $table) => $table->dropConstrainedForeignId('gudang_id'));
        Schema::table('gudangs', fn(Blueprint $table) => $table->dropColumn([
            'kode',
            'jenis',
            'alamat',
            'aktif',
            'boleh_penerimaan',
            'boleh_npk',
            'boleh_transfer',
            'boleh_opname',
        ]));
        Schema::rename('gudangs', 'admin_namagudang');
    }
};
