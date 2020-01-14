<?php

namespace App\Console;

use App\Console\Commands\CalculateFundUsersCommand;
use App\Console\Commands\CheckFundConfigCommand;
use App\Console\Commands\CheckFundStateCommand;
use App\Console\Commands\NotifyAboutReachedNotificationFundAmount;
use App\Console\Commands\NotifyAboutVoucherExpireCommand;
use App\Console\Commands\UpdateFundProviderInvitationExpireStateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CheckFundStateCommand::class,
        CheckFundConfigCommand::class,
        CalculateFundUsersCommand::class,
        NotifyAboutVoucherExpireCommand::class,
        NotifyAboutReachedNotificationFundAmount::class,
        UpdateFundProviderInvitationExpireStateCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('forus.fund:check')
            ->hourlyAt(1)->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund.config:check')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund.users:calculate')
            ->monthly()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.voucher:check-expire')
            ->dailyAt('09:00')->withoutOverlapping()->onOneServer();

        $schedule->command('forus.funds.provider_invitations:check-expire')
            ->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund:check-amount')
            ->cron('0 */8 * * *')->withoutOverlapping()->onOneServer();
  
        $schedule->command('digid:session-clean')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        if (in_array(config('queue.default'), ['redis', 'database'])) {
            $schedule->command('queue:work --queue=emails')
                ->everyMinute()->withoutOverlapping()->onOneServer();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
