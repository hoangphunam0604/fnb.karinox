<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;

class UpdateHappyMondayVoucher extends Command
{
  protected $signature = 'voucher:update-happy-monday';
  protected $description = 'Cập nhật danh sách ngày Thứ Hai cuối cùng của mỗi tháng cho Happy Monday Voucher';

  public function schedule(Schedule $schedule)
  {
    $schedule->yearlyOn(1, 1, '00:00'); // Chạy mỗi năm vào 1/1
  }

  public function handle()
  {
    $year = Carbon::now()->year;

    $voucher = Voucher::where('code', 'HAPPYMONDAY')->first();

    if (!$voucher) {
      $this->error('Voucher HAPPYMONDAY không tồn tại.');
      return;
    }

    $voucher->update([
      'valid_dates' => json_encode($this->getLastMondaysOfYear($year)),
      'excluded_dates' => json_encode([
        "$year-01-01",
        "$year-04-30",
        "$year-05-01",
        "$year-09-02",
        "$year-12-24",
        "$year-12-25",
        "$year-12-31"
      ]),
    ]);

    $this->info('Happy Monday Voucher đã được cập nhật thành công.');
  }

  private function getLastMondaysOfYear($year)
  {
    $lastMondays = [];

    for ($month = 1; $month <= 12; $month++) {
      $date = Carbon::create($year, $month, 1)->endOfMonth(); // Ngày cuối tháng
      while ($date->dayOfWeek !== Carbon::MONDAY) {
        $date->subDay(); // Lùi dần về Thứ Hai
      }
      $lastMondays[] = $date->toDateString();
    }

    return $lastMondays;
  }
}
