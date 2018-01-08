<?php

namespace App\Console;

use App\Jobs\CheckInvoiceStatusJob;
use App\Jobs\TransferJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\CheckInvoiceStatus',
        'App\Console\Commands\Transfer'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('check:invoice')->everyMinute();
        $schedule->command('transaction:transfer')->everyMinute();
    }
}
