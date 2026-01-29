<?php

namespace App\Console\Commands;

use App\Jobs\ResetExpiredArenaMembership;
use Illuminate\Console\Command;

class ResetExpiredArenaMembershipCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'arena:reset-expired-membership';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Reset gói arena member của các khách hàng đã hết hạn';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Đang reset các gói arena membership đã hết hạn...');

    $job = new ResetExpiredArenaMembership();
    $job->handle();

    $this->info('Hoàn tất! Kiểm tra log để xem chi tiết.');

    return Command::SUCCESS;
  }
}
