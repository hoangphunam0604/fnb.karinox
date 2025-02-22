<?php

namespace Tests\Unit;

use App\Enums\KitchenTicketStatus;
use App\Enums\UserRole;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\User;
use App\Services\KitchenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use App\Events\KitchenOrderReady;
use App\Models\Branch;
use App\Models\OrderItem;
use Spatie\Permission\Models\Role;

class KitchenServiceTest extends TestCase
{
  use RefreshDatabase;

  private KitchenService $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = new KitchenService();
  }
  #[Test]
  #[TestDox('Tạo vé bếp mới nếu chưa có vé cho đơn hàng')]
  public function test_it_creates_new_kitchen_ticket_if_none_exists()
  {
    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);

    $order = Order::factory()->hasItems(3)->create();

    $ticket = $this->service->addItemsToKitchen($order);

    $this->assertDatabaseHas('kitchen_tickets', ['order_id' => $order->id]);
    $this->assertDatabaseCount('kitchen_ticket_items', 3);
  }

  #[Test]
  #[TestDox('Thêm món vào vé bếp đã tồn tại')]
  public function test_it_adds_items_to_existing_kitchen_ticket()
  {

    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);
    $order = Order::factory()->hasItems(2)->create();

    // Tạo vé bếp đầu tiên
    $this->service->addItemsToKitchen($order);

    // Thêm món mới vào đơn hàng
    $newItems = OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

    $order->refresh();
    $this->service->addItemsToKitchen($order);
    $this->assertDatabaseCount('kitchen_ticket_items', 4); // 2 cũ + 2 mới
  }

  #[Test]
  #[TestDox('Không thêm trùng món vào vé bếp')]
  public function test_it_does_not_duplicate_existing_items()
  {
    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);

    $order = Order::factory()->hasItems(2)->create();

    // Tạo vé bếp ban đầu
    $this->service->addItemsToKitchen($order);

    // Gọi lại `addItemsToKitchen` mà không thêm món mới
    $this->service->addItemsToKitchen($order);

    $this->assertDatabaseCount('kitchen_ticket_items', 2); // Không tăng số lượng
  }

  #[Test]
  #[TestDox('Không tạo vé bếp nếu đơn hàng không có món')]
  public function test_it_does_not_create_ticket_if_order_has_no_items()
  {
    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);

    $order = Order::factory()->create(); // Không có món

    $ticket = $this->service->addItemsToKitchen($order);
    $this->assertNull($ticket); // ✅ Kiểm tra giá trị trả về là null
    $this->assertDatabaseCount('kitchen_tickets', 0); // ✅ Không có vé bếp nào được tạo
  }

  #[Test]
  #[TestDox('Chỉ trả về các vé bếp chưa hoàn thành')]
  public function test_it_returns_only_active_kitchen_tickets()
  {
    $branch = Branch::factory()->create();
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::WAITING]);
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::PROCESSING]);
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::COMPLETED]);

    $tickets = $this->service->getTickets($branch->id);

    $this->assertCount(2, $tickets->items());

    foreach ($tickets->items() as $ticket) {
      $this->assertNotEquals(KitchenTicketStatus::COMPLETED, $ticket->status);
    }
  }

  #[Test]
  #[TestDox('Lọc danh sách vé bếp theo trạng thái cụ thể')]
  public function test_it_filters_kitchen_tickets_by_status()
  {
    $branch = Branch::factory()->create();
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::WAITING]);
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::PROCESSING]);
    KitchenTicket::factory()->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::COMPLETED]);

    $tickets = $this->service->getTickets($branch->id, KitchenTicketStatus::WAITING);
    $this->assertCount(1, $tickets->items());
    $this->assertEquals(KitchenTicketStatus::WAITING, $tickets->items()[0]->status);
  }

  #[Test]
  #[TestDox('Đảm bảo vé bếp được phân trang đúng số lượng')]
  public function test_it_returns_paginated_kitchen_tickets()
  {
    $branch = Branch::factory()->create();
    KitchenTicket::factory()->count(15)->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::WAITING]);

    $tickets = $this->service->getTickets($branch->id, null, 10);

    $this->assertCount(10, $tickets->items());
    $this->assertEquals(15, $tickets->total());
  }

  #[Test]
  #[TestDox('Trả về danh sách rỗng nếu không có vé trong chi nhánh')]
  public function test_it_returns_empty_if_no_tickets_in_branch()
  {
    $tickets = $this->service->getTickets(2);

    $this->assertCount(0, $tickets->items());
  }

  #[Test]
  #[TestDox('Xóa vé bếp thành công')]
  public function test_it_can_delete_a_kitchen_ticket()
  {
    $ticket = KitchenTicket::factory()->create();

    $this->assertDatabaseHas('kitchen_tickets', ['id' => $ticket->id]);

    $this->service->deleteTicket($ticket->id);

    $this->assertDatabaseMissing('kitchen_tickets', ['id' => $ticket->id]);
  }

  #[Test]
  #[TestDox('Cập nhật trạng thái món ăn trong vé bếp')]
  public function test_it_can_update_item_status()
  {
    $ticket = KitchenTicket::factory()->hasItems(1)->create(['status' => KitchenTicketStatus::WAITING]);
    $item = $ticket->items->first();

    $this->service->updateItemStatus($item->id, KitchenTicketStatus::PROCESSING);

    $this->assertDatabaseHas('kitchen_ticket_items', [
      'id' => $item->id,
      'status' => KitchenTicketStatus::PROCESSING,
    ]);
  }

  #[Test]
  #[TestDox('Nhân viên bếp có thể nhận vé bếp')]
  public function test_it_can_accept_a_ticket()
  {

    // ✅ Tạo role nếu chưa có
    Role::firstOrCreate(['name' => UserRole::KITCHEN_STAFF, 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole(UserRole::KITCHEN_STAFF); // Spatie Role

    $ticket = KitchenTicket::factory()->create();

    $this->service->acceptTicket($ticket->id, $user->id);

    $this->assertDatabaseHas('kitchen_tickets', [
      'id' => $ticket->id,
      'accepted_by' => $user->id,
      'status' => KitchenTicketStatus::PROCESSING->value, // Đảm bảo là string nếu Enum
    ]);
  }

  #[Test]
  #[TestDox('Gửi thông báo cho nhân viên phục vụ khi món đã sẵn sàng')]
  public function test_it_can_notify_waiters()
  {
    Event::fake();

    // ✅ Tạo role nếu chưa có
    Role::firstOrCreate(['name' => UserRole::WAITER, 'guard_name' => 'web']);

    $ticket = KitchenTicket::factory()->create(['status' => KitchenTicketStatus::WAITING]);

    // Tạo 2 nhân viên phục vụ và gán role bằng Spatie
    $waiters = User::factory()->count(2)->create(['is_active' => true, 'last_seen_at' => now()]);
    $waiters->each(fn($user) => $user->assignRole(UserRole::WAITER));

    $this->service->notifyWaiter($ticket->id);

    Event::assertDispatched(KitchenOrderReady::class);
  }
}
