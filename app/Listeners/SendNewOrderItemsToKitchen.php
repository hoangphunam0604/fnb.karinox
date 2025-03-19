<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Events\OrderCompleted;
use App\Services\KitchenService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewOrderItemsToKitchen /* implements ShouldQueue */
{
  protected KitchenService $kitchenService;

  public function __construct(KitchenService $kitchenService)
  {
    $this->kitchenService = $kitchenService;
  }

  public function handle(OrderUpdated|OrderCompleted $event)
  {
    $this->kitchenService->addItemsToKitchen($event->order);
  }
}
