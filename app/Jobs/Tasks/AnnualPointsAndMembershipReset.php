<?php

namespace App\Jobs\Tasks;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnnualPointsAndMembershipReset implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle()
  {
    Customer::chunk(100, function ($customers) {
      dispatch(new AnnualPointsAndMembershipProcess($customers->pluck('id')->toArray()))->onQueue('low-priority');
    });
  }
}
