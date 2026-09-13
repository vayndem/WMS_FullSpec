<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('wms:hitung-replenishment')
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('wms:hitung-abc')
            ->weeklyOn(1, '04:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('wms:pengingat-harian')
            ->weekdays()
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('wms:penyusutan-bulanan')
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('queue:prune-failed --hours=720')->weeklyOn(7, '03:00');
        $schedule->command('auth:clear-resets')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
