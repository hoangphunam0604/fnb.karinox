<?php

namespace App\Console\Commands;

use App\Services\ProductDependencyService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class RebuildProductDependencies extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'product:rebuild-dependencies';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Rebuild all product stock dependencies';

  /**
   * Define the command's schedule.
   */
  public function schedule(Schedule $schedule): void
  {
    $schedule->command(static::class)->everyFiveMinutes();
  }

  /**
   * Execute the console command.
   */
  public function handle(ProductDependencyService $dependencyService): int
  {
    $this->info('Starting to rebuild product dependencies...');

    try {
      $dependencyService->rebuildAllDependencies();
      $this->info('✅ Successfully rebuilt all product dependencies!');
      return Command::SUCCESS;
    } catch (\Exception $e) {
      $this->error('❌ Error rebuilding dependencies: ' . $e->getMessage());
      return Command::FAILURE;
    }
  }
}
