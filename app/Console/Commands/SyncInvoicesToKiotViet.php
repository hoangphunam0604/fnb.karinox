<?php

namespace App\Console\Commands;

use App\Jobs\SyncInvoiceToKiotViet;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

class SyncInvoicesToKiotViet extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'invoices:sync-kiotviet 
                            {--limit=50 : Số lượng invoice tối đa mỗi lần chạy}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Đồng bộ các hóa đơn lên KiotViet (các hóa đơn thành công hơn 10 phút trước)';

  /**
   * Define the command's schedule.
   */
  public function schedule(Schedule $schedule): void
  {
    $schedule->command(static::class)->everyTenMinutes();
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $limit = (int) $this->option('limit');

    $this->info('Bắt đầu tìm các hóa đơn cần đồng bộ lên KiotViet...');

    // Tìm các invoice thỏa mãn điều kiện:
    // - Trạng thái completed
    // - Đã thanh toán (paid)
    // - Chưa đồng bộ lên KiotViet
    // - Được tạo hơn 10 phút trước
    $invoices = Invoice::where('invoice_status', 'completed')
      ->where('payment_status', 'paid')
      ->where('kiotviet_synced', false)
      ->where('created_at', '<=', now()->subMinutes(10))
      ->whereHas('branch', function ($query) {
        // Chỉ lấy invoice của branch có kiotviet_id
        $query->whereNotNull('kiotviet_id');
      })
      ->limit($limit)
      ->get();

    if ($invoices->isEmpty()) {
      $this->info('Không có hóa đơn nào cần đồng bộ.');
      return 0;
    }

    $this->info("Tìm thấy {$invoices->count()} hóa đơn cần đồng bộ.");

    $dispatched = 0;
    foreach ($invoices as $invoice) {
      try {
        // Đẩy job vào queue
        SyncInvoiceToKiotViet::dispatch($invoice);
        $dispatched++;

        $this->line("- Đã đưa hóa đơn {$invoice->code} vào hàng đợi");
      } catch (\Exception $e) {
        $this->error("Lỗi khi đưa hóa đơn {$invoice->code} vào hàng đợi: " . $e->getMessage());
        Log::error("Lỗi dispatch job SyncInvoiceToKiotViet", [
          'invoice_id' => $invoice->id,
          'exception' => $e->getMessage()
        ]);
      }
    }

    $this->info("Đã đưa {$dispatched} hóa đơn vào hàng đợi đồng bộ.");

    Log::info("Command invoices:sync-kiotviet đã chạy", [
      'invoices_found' => $invoices->count(),
      'dispatched' => $dispatched
    ]);

    return 0;
  }
}
