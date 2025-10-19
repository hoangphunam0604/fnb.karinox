<?php

namespace App\Console\Commands;

use App\Models\PrintQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPrintQueue extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'print:process-queue {--device= : Specific device ID} {--limit=10 : Number of jobs to process}';

  /**
   * The console command description.
   */
  protected $description = 'Process pending print jobs in the queue';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $deviceId = $this->option('device');
    $limit = (int) $this->option('limit');

    $this->info("Processing print queue" . ($deviceId ? " for device: {$deviceId}" : ""));

    $query = PrintQueue::pending()->byPriority();

    if ($deviceId) {
      $query->forDevice($deviceId);
    }

    $jobs = $query->limit($limit)->get();

    if ($jobs->isEmpty()) {
      $this->info("No pending print jobs found.");
      return;
    }

    $this->info("Found {$jobs->count()} pending print jobs.");

    foreach ($jobs as $job) {
      $this->processJob($job);
    }

    $this->info("Print queue processing completed.");
  }

  /**
   * Process a single print job
   */
  private function processJob(PrintQueue $job)
  {
    try {
      $this->info("Processing job #{$job->id} ({$job->type})");

      // Mark as processing
      $job->update(['status' => 'processing']);

      // Here you would integrate with actual printer
      // For now, we'll simulate processing
      $this->simulatePrintProcessing($job);

      // Mark as processed
      $job->markAsProcessed();

      $this->info("✓ Job #{$job->id} processed successfully");

      Log::info("Print job processed", [
        'job_id' => $job->id,
        'type' => $job->type,
        'device_id' => $job->device_id
      ]);
    } catch (\Exception $e) {
      $job->markAsFailed($e->getMessage());

      $this->error("✗ Job #{$job->id} failed: " . $e->getMessage());

      Log::error("Print job failed", [
        'job_id' => $job->id,
        'type' => $job->type,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Simulate print processing
   */
  private function simulatePrintProcessing(PrintQueue $job)
  {
    // Simulate different processing times based on print type
    $processingTimes = [
      'invoice' => 2,
      'provisional' => 1,
      'label' => 1,
      'kitchen' => 2
    ];

    $seconds = $processingTimes[$job->type] ?? 1;

    $this->output->write("  Processing");

    for ($i = 0; $i < $seconds; $i++) {
      sleep(1);
      $this->output->write(".");
    }

    $this->output->writeln(" Done!");

    // Simulate random failures (5% chance)
    if (rand(1, 100) <= 5) {
      throw new \Exception("Simulated printer error");
    }
  }
}
