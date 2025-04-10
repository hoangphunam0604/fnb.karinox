<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Enums\KitchenTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Kitchen\KitchenTicketItemResource;
use App\Services\KitchenService;
use Illuminate\Http\Request;

class KitchenItemController extends Controller
{
  protected $kitchenService;

  public function __construct(KitchenService $kitchenService)
  {
    $this->kitchenService = $kitchenService;
  }

  public function index()
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;
    $kitchenTicketItems = $this->kitchenService->getKitchenTicketItems($branchId);
    return response()->json([
      'success' => true,
      'data' => KitchenTicketItemResource::collection($kitchenTicketItems)
    ]);
  }
  public function processing($itemId)
  {
    $this->kitchenService->updateItemStatus($itemId, KitchenTicketStatus::PROCESSING);
  }
  public function completed($itemId)
  {
    $this->kitchenService->updateItemStatus($itemId, KitchenTicketStatus::COMPLETED);
  }
  public function completedItems(Request $request)
  {
    $ids = $request->all();
    return response()->json([$ids]);
  }
  public function cancel($itemId)
  {
    $this->kitchenService->updateItemStatus($itemId, KitchenTicketStatus::CANCELED);
  }
}
