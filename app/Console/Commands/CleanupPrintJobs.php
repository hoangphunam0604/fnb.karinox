<?php

namespace App\Console\Commands;

use App\Models\PrintQueue;
use Illuminate\Console\Command;

class CleanupPrintJobs extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'print:cleanup {--days=7 : Days to keep jobs} {--dry-run : Show what would be deleted without actually deleting}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Clean up old print jobs from the queue';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $days = (int) $this->option('days');
    $dryRun = $this->option('dry-run');

    $this->info("Cleaning up print jobs older than {$days} days");

    if ($dryRun) {
      $this->warn("DRY RUN MODE - No data will be deleted");
    }

    $cutoffDate = now()->subDays($days);

    // Get jobs to delete
    $query = PrintQueue::where('created_at', '<', $cutoffDate)
      ->whereIn('status', ['processed', 'failed']);

    $jobsCount = $query->count();

    if ($jobsCount === 0) {
      $this->info("No old print jobs found to cleanup.");
      return;
    }

    // Show breakdown by status
    $breakdown = PrintQueue::where('created_at', '<', $cutoffDate)
      ->whereIn('status', ['processed', 'failed'])
      ->selectRaw('status, COUNT(*) as count')
      ->groupBy('status')
      ->get();

    $this->info("Jobs to be deleted:");
    foreach ($breakdown as $item) {
      $this->line("  {$item->status}: {$item->count}");
    }

    if ($dryRun) {
      $this->info("DRY RUN: {$jobsCount} jobs would be deleted.");
      return;
    }

    if ($jobsCount > 100) {
      if (!$this->confirm("About to delete {$jobsCount} print jobs. Continue?")) {
        $this->info("Cleanup cancelled.");
        return;
      }
    }

    // Delete old jobs
    $deleted = $query->delete();

    $this->info("✅ Deleted {$deleted} old print jobs.");

    // Show remaining stats
    $remaining = PrintQueue::count();
    $this->info("📊 Remaining print jobs in queue: {$remaining}");
  }
}
