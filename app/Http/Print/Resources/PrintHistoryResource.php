<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintHistoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'branch_id' => $this->branch_id,
      'type' => $this->type,
      'type_label' => $this->getTypeLabel(),
      'status' => $this->status,
      'status_label' => $this->getStatusLabel(),
      'status_color' => $this->getStatusColor(),

      // Metadata với thông tin chi tiết
      'metadata' => $this->metadata,
      'order_info' => $this->getOrderInfo(),

      // Timestamps formatted
      'requested_at' => $this->requested_at?->format('Y-m-d H:i:s'),
      'printed_at' => $this->printed_at?->format('Y-m-d H:i:s'),

      // Formatted timestamps for display
      'requested_at_formatted' => $this->requested_at?->format('d/m/Y H:i'),
      'printed_at_formatted' => $this->printed_at?->format('d/m/Y H:i'),

      // Branch information
      'branch' => $this->whenLoaded('branch', function () {
        return [
          'id' => $this->branch->id,
          'name' => $this->branch->name,
          'address' => $this->branch->address ?? null,
        ];
      }),

      // Timestamps
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }

  /**
   * Get print type label in Vietnamese
   */
  private function getTypeLabel(): string
  {
    return match ($this->type) {
      'order' => 'Đơn hàng',
      'receipt' => 'Hóa đơn',
      'kitchen' => 'Bếp',
      'bar' => 'Quầy bar',
      'report' => 'Báo cáo',
      'invoice' => 'Hóa đơn VAT',
      default => ucfirst($this->type),
    };
  }

  /**
   * Get status label in Vietnamese
   */
  private function getStatusLabel(): string
  {
    return match ($this->status) {
      'requested' => 'Chờ in',
      'printed' => 'Hoàn thành',
      'failed' => 'Lỗi',
      default => ucfirst($this->status),
    };
  }

  /**
   * Get status color for UI
   */
  private function getStatusColor(): string
  {
    return match ($this->status) {
      'requested' => 'orange',
      'printed' => 'success',
      'failed' => 'red',
      default => 'grey',
    };
  }

  /**
   * Get order information from metadata
   */
  private function getOrderInfo(): ?array
  {
    if (empty($this->metadata['order_id'])) {
      return null;
    }

    return [
      'order_id' => $this->metadata['order_id'] ?? null,
      'order_code' => $this->metadata['order_code'] ?? null,
      'table_name' => $this->metadata['table_name'] ?? null,
      'customer_name' => $this->metadata['customer_name'] ?? null,
      'total_amount' => $this->metadata['total_amount'] ?? null,
      'items_count' => $this->metadata['items_count'] ?? 0,
    ];
  }
}
