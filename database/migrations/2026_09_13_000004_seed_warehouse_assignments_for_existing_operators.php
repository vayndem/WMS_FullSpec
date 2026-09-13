<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $gudangIds = DB::table('gudangs')->where('aktif', true)->pluck('id');
        $userIds = DB::table('users')->where('type', User::ROLE_WAREHOUSE)->pluck('id');

        if ($gudangIds->isEmpty() || $userIds->isEmpty()) {
            return;
        }

        $sudahAda = DB::table('pembagian_gudangs')
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->unique();

        $baris = [];
        foreach ($userIds->diff($sudahAda) as $userId) {
            foreach ($gudangIds as $gudangId) {
                $baris[] = [
                    'user_id' => $userId,
                    'gudang_id' => $gudangId,
                    'boleh_menerima' => true,
                    'boleh_npk' => true,
                    'boleh_transfer' => true,
                    'boleh_opname' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($baris !== []) {
            DB::table('pembagian_gudangs')->insert($baris);
        }
    }

    public function down(): void
    {
    }
};
