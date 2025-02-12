<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetMembershipPoints implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle()
  {
    Customer::chunk(500, function ($customers) {
      dispatch(new ProcessResetMembership($customers->pluck('id')->toArray()));
    });
  }
}
