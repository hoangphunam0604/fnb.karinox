<?php

namespace App\Console;

use App\Jobs\ResetMembershipPoints;
use App\Jobs\UpdateMembershipLevelsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   */
  protected function schedule(Schedule $schedule): void
  {
    // Lên lịch chạy reset điểm vào ngày 1/1 mỗi năm lúc 00:00
    $schedule->job(new ResetMembershipPoints())->yearlyOn(1, 1, '00:00');
    $schedule->job(new UpdateMembershipLevelsJob())->everyFiveMinutes();
  }

  /**
   * Register the commands for the application.
   */
  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
