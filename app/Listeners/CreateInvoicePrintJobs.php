<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateInvoicePrintJobs implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Handle the event - Broadcast notification để frontend biết có invoice mới
   * Frontend sẽ call API để lấy print data
   */
  public function handle(InvoiceCreated $event): void
  {
    $invoice = $event->invoice;

    try {
      // Broadcast notification NHẸ qua WebSocket
      broadcast(new \App\Events\PrintRequested([
        'event' => 'invoice.created',
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'branch_id' => $invoice->branch_id,
        'created_at' => $invoice->created_at->toIso8601String()
      ], $invoice->branch_id));

      Log::info('Invoice print notification sent', [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code
      ]);
    } catch (\Exception $exception) {
      Log::error('Failed to send invoice print notification', [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'error' => $exception->getMessage()
      ]);
    }
  }
}
