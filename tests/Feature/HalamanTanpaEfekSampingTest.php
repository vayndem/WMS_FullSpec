<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HalamanTanpaEfekSampingTest extends TestCase
{
    use DatabaseTransactions;

    private const LEWATI = [
        'ignition.healthCheck', 'ignition.executeSolution', 'ignition.updateConfig',
        'password.reset', 'logout',
    ];

    private const BOLEH_MENULIS = [
        'notifikasi.index',
    ];

    public function test_no_get_page_writes_to_the_database(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        $pelanggar = [];
        $diperiksa = 0;

        foreach (Route::getRoutes() as $route) {
            $nama = $route->getName();

            if (!$nama || !in_array('GET', $route->methods(), true)) {
                continue;
            }

            if (in_array($nama, self::LEWATI, true) || in_array($nama, self::BOLEH_MENULIS, true)) {
                continue;
            }

            if ($route->parameterNames()) {
                continue;
            }

            $tulisan = [];

            DB::listen(function ($query) use (&$tulisan) {
                if (preg_match('/^\s*(insert|update|delete|replace)\s/i', $query->sql)) {
                    $tulisan[] = trim(preg_replace('/\s+/', ' ', substr($query->sql, 0, 120)));
                }
            });

            $response = $this->actingAs($admin)->get(route($nama));

            DB::flushQueryLog();
            $this->app['events']->forget('Illuminate\Database\Events\QueryExecuted');

            $diperiksa++;

            if ($response->getStatusCode() >= 500) {
                $pelanggar[] = "{$nama} => HTTP {$response->getStatusCode()}";
                continue;
            }

            if ($tulisan) {
                $pelanggar[] = "{$nama} => menulis ke database: " . $tulisan[0];
            }
        }

        $this->assertGreaterThan(100, $diperiksa, 'Terlalu sedikit halaman yang diperiksa.');
        $this->assertSame(
            [],
            $pelanggar,
            "Halaman GET berikut punya efek samping ke database:\n" . implode("\n", $pelanggar)
        );
    }
}
