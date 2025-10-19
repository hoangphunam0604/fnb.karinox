<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;

class MockDataService
{
  /**
   * Generate mock order data cho test print
   */
  public function generateMockOrder(string $type = 'simple', ?int $branchId = null): array
  {
    $branch = $branchId ? Branch::find($branchId) : Branch::first();

    $baseOrder = [
      'id' => 999999,
      'order_code' => 'TEST-' . date('YmdHis'),
      'table_id' => 1,
      'table_name' => 'Bàn Test-01',
      'area_name' => 'Khu vực Test',
      'branch_name' => $branch?->name ?? 'Karinox Coffee Test',
      'branch_address' => $branch?->address ?? '123 Đường Test, Quận Test, TP.HCM',
      'branch_phone' => $branch?->phone ?? '0901234567',
      'staff_name' => 'Nhân viên Test',
      'created_at' => now(),
      'updated_at' => now(),
      'subtotal' => 0,
      'discount_amount' => 0,
      'total_amount' => 0,
      'status' => 'confirmed',
      'payment_status' => 'pending'
    ];

    switch ($type) {
      case 'simple':
        return $this->addSimpleItems($baseOrder);
      case 'complex':
        return $this->addComplexItems($baseOrder);
      case 'with_toppings':
        return $this->addItemsWithToppings($baseOrder);
      case 'large_order':
        return $this->addLargeOrderItems($baseOrder);
      default:
        return $this->addSimpleItems($baseOrder);
    }
  }

  /**
   * Đơn hàng đơn giản
   */
  private function addSimpleItems(array $order): array
  {
    $order['items'] = [
      [
        'id' => 1,
        'product_name' => 'Cà phê đen',
        'quantity' => 2,
        'price' => 25000,
        'total' => 50000,
        'note' => '',
        'toppings' => []
      ],
      [
        'id' => 2,
        'product_name' => 'Trà sữa truyền thống',
        'quantity' => 1,
        'price' => 35000,
        'total' => 35000,
        'note' => '',
        'toppings' => []
      ]
    ];

    $order['subtotal'] = 85000;
    $order['total_amount'] = 85000;
    $order['items_count'] = 3;

    return $order;
  }

  /**
   * Đơn hàng phức tạp với nhiều sản phẩm
   */
  private function addComplexItems(array $order): array
  {
    $order['items'] = [
      [
        'id' => 1,
        'product_name' => 'Combo Cà phê + Bánh',
        'quantity' => 1,
        'price' => 65000,
        'total' => 65000,
        'note' => 'Không đường',
        'toppings' => []
      ],
      [
        'id' => 2,
        'product_name' => 'Sinh tố bơ',
        'quantity' => 2,
        'price' => 45000,
        'total' => 90000,
        'note' => 'Ít đá',
        'toppings' => []
      ],
      [
        'id' => 3,
        'product_name' => 'Bánh mì thịt nướng',
        'quantity' => 1,
        'price' => 28000,
        'total' => 28000,
        'note' => '',
        'toppings' => []
      ]
    ];

    $order['discount_amount'] = 10000;
    $order['subtotal'] = 183000;
    $order['total_amount'] = 173000;
    $order['items_count'] = 4;
    $order['voucher_code'] = 'TESTDISCOUNT';

    // Customer info
    $order['customer'] = [
      'name' => 'Nguyễn Văn Test',
      'phone' => '0987654321',
      'email' => 'test@example.com',
      'membership_level' => 'Gold',
      'reward_points_used' => 50,
      'reward_points_earned' => 17
    ];

    return $order;
  }

  /**
   * Đơn hàng có topping
   */
  private function addItemsWithToppings(array $order): array
  {
    $order['items'] = [
      [
        'id' => 1,
        'product_name' => 'Trà sữa socola',
        'quantity' => 1,
        'price' => 40000,
        'total' => 55000,
        'note' => 'Ít ngọt',
        'toppings' => [
          ['name' => 'Trân châu đen', 'price' => 8000],
          ['name' => 'Thạch rau câu', 'price' => 7000]
        ]
      ],
      [
        'id' => 2,
        'product_name' => 'Cà phê sữa đá',
        'quantity' => 2,
        'price' => 30000,
        'total' => 72000,
        'note' => '',
        'toppings' => [
          ['name' => 'Shot thêm', 'price' => 6000]
        ]
      ]
    ];

    $order['subtotal'] = 127000;
    $order['total_amount'] = 127000;
    $order['items_count'] = 3;

    return $order;
  }

  /**
   * Đơn hàng lớn nhiều món
   */
  private function addLargeOrderItems(array $order): array
  {
    $order['items'] = [
      [
        'id' => 1,
        'product_name' => 'Cà phê đen',
        'quantity' => 5,
        'price' => 25000,
        'total' => 125000,
        'note' => '',
        'toppings' => []
      ],
      [
        'id' => 2,
        'product_name' => 'Trà sữa truyền thống',
        'quantity' => 3,
        'price' => 35000,
        'total' => 105000,
        'note' => '',
        'toppings' => []
      ],
      [
        'id' => 3,
        'product_name' => 'Bánh mì pate',
        'quantity' => 4,
        'price' => 22000,
        'total' => 88000,
        'note' => '',
        'toppings' => []
      ],
      [
        'id' => 4,
        'product_name' => 'Nước cam ép',
        'quantity' => 2,
        'price' => 35000,
        'total' => 70000,
        'note' => 'Không đá',
        'toppings' => []
      ],
      [
        'id' => 5,
        'product_name' => 'Combo Set A',
        'quantity' => 1,
        'price' => 85000,
        'total' => 85000,
        'note' => '',
        'toppings' => []
      ]
    ];

    $order['subtotal'] = 473000;
    $order['discount_amount'] = 25000;
    $order['total_amount'] = 448000;
    $order['items_count'] = 15;
    $order['voucher_code'] = 'BULK20';
    $order['table_name'] = 'Bàn VIP-08';

    // Customer info cho large order
    $order['customer'] = [
      'name' => 'Công ty ABC',
      'phone' => '0901234567',
      'email' => 'order@company-abc.com',
      'membership_level' => 'Platinum',
      'reward_points_used' => 200,
      'reward_points_earned' => 45
    ];

    return $order;
  }

  /**
   * Generate mock data cho kitchen ticket
   */
  public function generateKitchenTicketData(array $order): array
  {
    // Lọc chỉ những items cần làm bếp
    $kitchenItems = array_filter($order['items'], function ($item) {
      return in_array($item['product_name'], [
        'Bánh mì thịt nướng',
        'Bánh mì pate',
        'Combo Cà phê + Bánh',
        'Combo Set A',
        'Sinh tố bơ'
      ]);
    });

    return [
      'order_code' => $order['order_code'],
      'table_name' => $order['table_name'],
      'items' => array_values($kitchenItems),
      'created_at' => $order['created_at'],
      'notes' => 'Đơn hàng test - Ưu tiên thực hiện',
      'priority' => 'high'
    ];
  }

  /**
   * Generate mock data cho label printing
   */
  public function generateLabelData(array $order): array
  {
    $labels = [];

    foreach ($order['items'] as $item) {
      for ($i = 0; $i < $item['quantity']; $i++) {
        $labels[] = [
          'product_name' => $item['product_name'],
          'table_name' => $order['table_name'],
          'order_code' => $order['order_code'],
          'item_number' => $i + 1,
          'total_quantity' => $item['quantity'],
          'note' => $item['note'],
          'toppings' => $item['toppings'] ?? [],
          'created_at' => $order['created_at']
        ];
      }
    }

    return $labels;
  }
}
