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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:work sqs --sleep=3 --tries=3 --max-time=3600 --queue='.env('AWS_SQS_CREATEWALLET_QUEUE').','.env('AWS_SQS_DISBURSEADREVENUE_QUEUE'))->environments(['local', 'staging']);
    }
}
