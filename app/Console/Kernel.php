<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Jobs\Test::class,
        Jobs\AutoFreeUser::class,
        Jobs\ComputeSignCount::class,
        Jobs\ComputeUserDailyStat::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('AutoFreeUser')->everyFiveMinutes();
        $schedule->command('ComputeSignCount')->dailyAt('00:30');
        $schedule->command('ComputeUserDailyStat')->dailyAt('00:01');
        $schedule->command('Test')->everyMinute()->withoutOverlapping();
    }
}
