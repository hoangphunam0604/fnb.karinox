<?php

namespace App\Jobs\Membership;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMembershipUpdate implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $customer;

  public function __construct(Customer $customer)
  {
    $this->customer = $customer;
  }

  public function handle()
  {
    $this->customer->updateMembershipLevel();
  }
}
