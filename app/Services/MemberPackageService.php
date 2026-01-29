<?php

namespace App\Services;

use App\Enums\ProductArenaType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberPackageService
{
  /**
   * Xử lý gói hội viên từ order items sau khi invoice hoàn thành
   * 
   * @param Invoice $invoice
   * @throws Exception
   */
  public function processMemberPackages(Invoice $invoice): void
  {
    // Kiểm tra invoice có khách hàng không
    if (!$invoice->customer_id) {
      Log::warning('Cannot process member packages: Invoice has no customer', [
        'invoice_id' => $invoice->id
      ]);
      throw new Exception('Không thể xử lý gói hội viên: Giao dịch chưa được thêm khách hàng.');
    }

    $order = $invoice->order;
    if (!$order) {
      Log::warning('Cannot process member packages: Invoice has no order', [
        'invoice_id' => $invoice->id
      ]);
      return;
    }

    // Lấy tất cả items là gói hội viên
    $memberPackageItems = $order->items()->get()->filter(function ($item) {
      return $this->isMemberPackageItem($item);
    });

    if ($memberPackageItems->isEmpty()) {
      return;
    }

    foreach ($memberPackageItems as $item) {
      $this->processSingleMemberPackage($item, $invoice->customer);
    }
  }

  /**
   * Kiểm tra order item có phải là gói hội viên không
   */
  private function isMemberPackageItem(OrderItem $item): bool
  {
    return in_array($item->arena_type->value, [
      ProductArenaType::ARENA_MEMBER->value,
      ProductArenaType::ARENA_MORNING_MEMBER->value,
      ProductArenaType::ARENA_RELAX_MEMBER->value,
      ProductArenaType::ARENA_VIP->value,
      ProductArenaType::ARENA_VVIP->value,
      ProductArenaType::ARENA_MASTER->value,
    ]);
  }

  /**
   * Xử lý một gói hội viên
   */
  private function processSingleMemberPackage(OrderItem $item, Customer $customer): void
  {
    try {
      // Parse thông tin từ note (JSON)
      $noteData = json_decode($item->note, true);

      if (!$noteData || !isset($noteData['expiryDate'])) {
        Log::warning('Invalid member package note data', [
          'order_item_id' => $item->id,
          'note' => $item->note
        ]);
        throw new Exception('Thông tin gói hội viên không hợp lệ.');
      }

      $expiryDate = $noteData['expiryDate']; // Format: dd/mm/yyyy

      // Convert từ dd/mm/yyyy sang Carbon date
      $expiryDateTime = Carbon::createFromFormat('d/m/Y', $expiryDate)->endOfDay();

      // Lấy loại gói hội viên từ arena_type
      $memberType = $this->getMemberTypeFromArenaType($item->arena_type);

      // Cập nhật thông tin gói hội viên cho khách hàng
      DB::transaction(function () use ($customer, $memberType, $expiryDateTime, $item) {
        $customer->arena_member = $memberType;
        $customer->arena_member_exp = $expiryDateTime;
        $customer->save();

        Log::info('Member package activated successfully', [
          'customer_id' => $customer->id,
          'arena_member' => $memberType,
          'arena_member_exp' => $expiryDateTime->format('Y-m-d'),
          'order_item_id' => $item->id
        ]);
      });
    } catch (Exception $e) {
      Log::error('Error processing member package', [
        'order_item_id' => $item->id,
        'customer_id' => $customer->id,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Chuyển đổi ProductArenaType sang loại gói hội viên cho customer
   */
  private function getMemberTypeFromArenaType(ProductArenaType $arenaType): string
  {
    return match ($arenaType) {
      ProductArenaType::ARENA_MEMBER => 'member',
      ProductArenaType::ARENA_MORNING_MEMBER => 'morning_member',
      ProductArenaType::ARENA_RELAX_MEMBER => 'relax_member',
      ProductArenaType::ARENA_VIP => 'vip',
      ProductArenaType::ARENA_VVIP => 'vvip',
      ProductArenaType::ARENA_MASTER => 'master',
      default => 'none',
    };
  }

  /**
   * Kiểm tra xem có order items nào là gói hội viên không
   */
  public function hasMemberPackages($order): bool
  {
    return $order->items->contains(function ($item) {
      return $this->isMemberPackageItem($item);
    });
  }
}
