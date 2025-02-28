<?php

namespace App\Console;

use App\Jobs\Tasks\AnnualPointsAndMembershipReset;
use App\Jobs\Membership\UpdateMembershipLevelsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   */
  protected function schedule(Schedule $schedule): void
  {
    // Lên lịch chạy reset điểm vào ngày 1/3 mỗi năm lúc 00:05
    $schedule->job(new AnnualPointsAndMembershipReset())->yearlyOn(3, 1, '00:05');

    //Lên lịch cập nhật hạng thành viên mỗi 5 phút
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
