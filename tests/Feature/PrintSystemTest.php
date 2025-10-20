<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Http\POS\Controllers\PrintController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PrintSystemTest extends TestCase
{
  use RefreshDatabase, WithFaker;

  protected $user;
  protected $customer;
  protected $order;

  protected function setUp(): void
  {
    parent::setUp();

    // Seed necessary data
    $this->seed();

    // Get existing user and create test order manually
    $this->user = User::first();
    $this->customer = Customer::first();

    if (!$this->user) {
      $this->user = User::create([
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => bcrypt('password'),
        'role' => 'admin'
      ]);
    }

    if (!$this->customer) {
      $this->customer = Customer::create([
        'fullname' => 'Test Customer',
        'phone' => '0123456789',
        'email' => 'test@example.com',
        'gender' => 'male',
        'status' => 'active'
      ]);
    }

    $this->order = Order::create([
      'code' => 'TEST-' . now()->format('YmdHis'),
      'customer_id' => $this->customer->id,
      'branch_id' => 1,
      'table_id' => 1,
      'subtotal_price' => 50000,
      'total_price' => 50000,
      'order_status' => 'completed',
      'payment_status' => 'paid',
      'payment_method' => 'cash'
    ]);

    // Mock items relationship để tránh lỗi
    $this->order->setRelation('items', collect());
  }

  /**
   * Test print provisional via POS API
   */
  public function test_print_provisional_order(): void
  {
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$this->order->id}/provisional");

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Đã gửi lệnh in tạm tính'
      ])
      ->assertJsonStructure([
        'data' => [
          'print_id',
          'print_type',
          'status'
        ]
      ]);

    // Assert print history was created
    $this->assertDatabaseHas('print_histories', [
      'type' => 'other', // provisional maps to 'other'
      'status' => 'requested'
    ]);
  }

  /**
   * Test print invoice via POS API
   */
  public function test_print_invoice_order(): void
  {
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$this->order->id}/invoice");

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Đã gửi lệnh in hóa đơn'
      ]);

    // Assert print history was created with invoice type
    $this->assertDatabaseHas('print_histories', [
      'type' => 'invoice',
      'status' => 'requested'
    ]);
  }

  /**
   * Test print kitchen ticket via POS API
   */
  public function test_print_kitchen_ticket(): void
  {
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$this->order->id}/kitchen");

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Đã gửi lệnh in phiếu bếp'
      ]);
  }

  /**
   * Test auto print functionality
   */
  public function test_auto_print_after_payment(): void
  {
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$this->order->id}/auto-print");

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Đã gửi lệnh in tự động'
      ])
      ->assertJsonStructure([
        'data' => [
          'print_ids' => [
            'invoice',
            'kitchen'
          ]
        ]
      ]);
  }

  /**
   * Test get print status
   */
  public function test_get_print_status(): void
  {
    // First create a print job
    $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$this->order->id}/invoice");

    // Then check status
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->getJson("/api/pos/orders/{$this->order->id}/print-status");

    $response->assertStatus(200)
      ->assertJson(['success' => true])
      ->assertJsonStructure([
        'data' => [
          'invoice' => [
            'status',
            'created_at'
          ]
        ]
      ]);
  }

  /**
   * Test print unpaid order should fail
   */
  public function test_print_invoice_unpaid_order_fails(): void
  {
    $unpaidOrder = Order::create([
      'code' => 'UNPAID-' . now()->format('YmdHis'),
      'customer_id' => $this->customer->id,
      'branch_id' => 1,
      'table_id' => 1,
      'subtotal_price' => 30000,
      'total_price' => 30000,
      'order_status' => 'pending',
      'payment_status' => 'unpaid',
      'payment_method' => 'cash'
    ]);

    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/{$unpaidOrder->id}/invoice");

    $response->assertStatus(400)
      ->assertJson([
        'success' => false,
        'message' => 'Đơn hàng chưa được thanh toán'
      ]);
  }

  /**
   * Test print nonexistent order
   */
  public function test_print_nonexistent_order_fails(): void
  {
    $response = $this->actingAs($this->user, 'api')
      ->withHeaders([
        'karinox-app-id' => 'karinox-app-pos',
        'X-Branch-Id' => '1'
      ])
      ->postJson("/api/pos/orders/99999/invoice");

    $response->assertStatus(404);
  }

  /**
   * Test unauthorized access
   */
  public function test_unauthorized_print_access_fails(): void
  {
    $response = $this->postJson("/api/pos/orders/{$this->order->id}/invoice");

    $response->assertStatus(401);
  }
}
