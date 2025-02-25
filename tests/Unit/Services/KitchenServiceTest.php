<?php

namespace Tests\Unit;

use App\Enums\KitchenTicketStatus;
use App\Enums\OrderItemStatus;
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
use App\Models\KitchenTicketItem;
use App\Models\OrderItem;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KitchenServiceTest extends TestCase
{
  use RefreshDatabase;

  private KitchenService $kitchenService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->kitchenService = new KitchenService();
    // ✅ Tạo role nếu chưa có
    Role::firstOrCreate(['name' => UserRole::WAITER, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::KITCHEN_STAFF, 'guard_name' => 'web']);
  }
  #[Test]
  #[TestDox('Tạo vé bếp mới nếu chưa có vé cho đơn hàng')]
  public function test_it_creates_new_kitchen_ticket_if_none_exists()
  {
    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);

    $order = Order::factory()->hasItems(3)->create();

    $ticket = $this->kitchenService->addItemsToKitchen($order);

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
    $this->kitchenService->addItemsToKitchen($order);

    // Thêm món mới vào đơn hàng
    $newItems = OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

    $order->refresh();
    $this->kitchenService->addItemsToKitchen($order);
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
    $this->kitchenService->addItemsToKitchen($order);

    // Gọi lại `addItemsToKitchen` mà không thêm món mới
    $this->kitchenService->addItemsToKitchen($order);

    $this->assertDatabaseCount('kitchen_ticket_items', 2); // Không tăng số lượng
  }

  #[Test]
  #[TestDox('Không tạo vé bếp nếu đơn hàng không có món')]
  public function test_it_does_not_create_ticket_if_order_has_no_items()
  {
    $user = User::factory()->create(); // Tạo user trước
    Auth::shouldReceive('id')->andReturn($user->id);

    $order = Order::factory()->create(); // Không có món

    $ticket = $this->kitchenService->addItemsToKitchen($order);
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

    $tickets = $this->kitchenService->getTickets($branch->id);

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

    $tickets = $this->kitchenService->getTickets($branch->id, KitchenTicketStatus::WAITING);
    $this->assertCount(1, $tickets->items());
    $this->assertEquals(KitchenTicketStatus::WAITING, $tickets->items()[0]->status);
  }

  #[Test]
  #[TestDox('Đảm bảo vé bếp được phân trang đúng số lượng')]
  public function test_it_returns_paginated_kitchen_tickets()
  {
    $branch = Branch::factory()->create();
    KitchenTicket::factory()->count(15)->create(['branch_id' => $branch->id, 'status' => KitchenTicketStatus::WAITING]);

    $tickets = $this->kitchenService->getTickets($branch->id, null, 10);

    $this->assertCount(10, $tickets->items());
    $this->assertEquals(15, $tickets->total());
  }

  #[Test]
  #[TestDox('Trả về danh sách rỗng nếu không có vé trong chi nhánh')]
  public function test_it_returns_empty_if_no_tickets_in_branch()
  {
    $tickets = $this->kitchenService->getTickets(2);

    $this->assertCount(0, $tickets->items());
  }

  #[Test]
  #[TestDox('Xóa vé bếp thành công')]
  public function test_it_can_delete_a_kitchen_ticket()
  {
    $ticket = KitchenTicket::factory()->create();

    $this->assertDatabaseHas('kitchen_tickets', ['id' => $ticket->id]);

    $this->kitchenService->deleteTicket($ticket->id);

    $this->assertDatabaseMissing('kitchen_tickets', ['id' => $ticket->id]);
  }

  #[Test]
  #[TestDox('Cập nhật trạng thái món ăn trong vé bếp')]
  public function test_it_can_update_item_status()
  {
    $ticket = KitchenTicket::factory()->create(['status' => KitchenTicketStatus::WAITING]);
    $item = KitchenTicketItem::factory()->create([
      'kitchen_ticket_id' => $ticket->id,
      'status'  =>  KitchenTicketStatus::PROCESSING
    ]);

    $this->kitchenService->updateItemStatus($item->id, KitchenTicketStatus::WAITING);

    $this->assertDatabaseHas('kitchen_ticket_items', [
      'id' => $item->id,
      'status' => KitchenTicketStatus::PROCESSING,
    ]);
  }

  #[Test]
  #[TestDox("Nhân viên bếp có thể nhận vé bếp thành công")]
  public function test_kitchen_staff_can_accept_ticket()
  {
    // ✅ Tạo role nếu chưa có
    $user = User::factory()->create();
    $user->assignRole(UserRole::KITCHEN_STAFF);

    $ticket = KitchenTicket::factory()->create([
      'status' => KitchenTicketStatus::WAITING,
      'accepted_by' => null
    ]);

    $this->kitchenService->acceptTicket($ticket->id, $user->id);

    $this->assertDatabaseHas('kitchen_tickets', [
      'id' => $ticket->id,
      'accepted_by' => $user->id,
      'status' => KitchenTicketStatus::PROCESSING->value
    ]);
  }

  #[Test]
  #[TestDox("Ném lỗi nếu người dùng không tồn tại")]
  public function test_throws_error_when_user_not_found()
  {
    $this->expectException(ModelNotFoundException::class);
    $this->kitchenService->acceptTicket(1, 999); // User ID không tồn tại
  }

  #[Test]
  #[TestDox("Ném lỗi nếu người dùng không phải là nhân viên bếp")]
  public function test_throws_error_if_user_is_not_kitchen_staff()
  {
    $user = User::factory()->create();
    $user->assignRole(UserRole::WAITER); // Không phải nhân viên bếp
    $ticket = KitchenTicket::factory()->create(['status' => KitchenTicketStatus::WAITING]);

    // Chặn xử lý lỗi mặc định để test abort
    $this->withoutExceptionHandling();
    // Bắt ngoại lệ
    try {
      $this->kitchenService->acceptTicket($ticket->id, $user->id);
    } catch (HttpException | AuthorizationException $e) {
      $this->assertEquals(403, $e->getStatusCode());
      $this->assertEquals("Chỉ nhân viên bếp mới có thể nhận món.", $e->getMessage());
      return;
    }

    $this->fail('Expected HttpException (403) was not thrown.');
  }

  #[Test]
  #[TestDox("Ném lỗi nếu vé bếp đã có người nhận")]
  public function test_throws_error_if_ticket_is_already_accepted()
  {
    $user = User::factory()->create();
    $user->assignRole(UserRole::KITCHEN_STAFF);

    $acceptedBy = User::factory()->create();
    $acceptedBy->assignRole(UserRole::KITCHEN_STAFF);

    $ticket = KitchenTicket::factory()->create([
      'status' => KitchenTicketStatus::WAITING,
      'accepted_by' => $acceptedBy->id
    ]);
    // Chặn xử lý lỗi mặc định để test abort
    $this->withoutExceptionHandling();
    // Bắt ngoại lệ
    try {
      $this->kitchenService->acceptTicket($ticket->id, $user->id);
    } catch (HttpException | AuthorizationException $e) {
      $this->assertEquals(403, $e->getStatusCode());
      $this->assertEquals("Vé bếp này không thể được nhận vì đã được tiếp nhận bởi: {$acceptedBy->fullname}.", $e->getMessage());
      return;
    }
    $this->fail('Expected HttpException (403) was not thrown.');
  }

  #[Test]
  #[TestDox("Ném lỗi nếu vé bếp không ở trạng thái WAITING")]
  public function test_throws_error_if_ticket_status_is_not_waiting()
  {
    $user = User::factory()->create();
    $user->assignRole(UserRole::KITCHEN_STAFF);
    // Các trạng thái không hợp lệ (không phải WAITING)
    $invalidStatuses = [
      KitchenTicketStatus::PROCESSING,
      KitchenTicketStatus::COMPLETED,
      KitchenTicketStatus::CANCELED,
    ];

    foreach ($invalidStatuses as $status) {
      // Tạo vé bếp với trạng thái không phải WAITING
      $ticket = KitchenTicket::factory()->create([
        'status' => $status,
      ]);

      // Chặn xử lý exception mặc định để kiểm tra abort
      $this->withoutExceptionHandling();

      // Bắt ngoại lệ do abort(403)
      try {
        $this->kitchenService->acceptTicket($ticket->id, $user->id);
      } catch (HttpException $e) {
        $this->assertEquals(403, $e->getStatusCode());
        $this->assertEquals("Vé bếp này không thể được nhận vì đang ở trạng thái: {$status->value}.", $e->getMessage());
        continue; // Kiểm tra tiếp với các trạng thái khác
      }

      // Nếu không ném lỗi, test sẽ fail
      $this->fail("Expected HttpException (403) was not thrown for status: {$status->value}");
    }
  }

  #[Test]
  #[TestDox("Khi nhận vé bếp, trạng thái món ăn và đơn hàng được cập nhật")]
  public function test_updates_order_items_status_when_ticket_is_accepted()
  {
    $user = User::factory()->create();
    $user->assignRole(UserRole::KITCHEN_STAFF);

    $ticket = KitchenTicket::factory()->create([
      'status' => KitchenTicketStatus::WAITING,
      'accepted_by' => null
    ]);
    $orderItem = OrderItem::factory()->create(['status' => OrderItemStatus::PENDING]);
    KitchenTicketItem::factory()->create([
      'kitchen_ticket_id' => $ticket->id,
      'order_item_id' => $orderItem->id
    ]);

    $this->kitchenService->acceptTicket($ticket->id, $user->id);

    $this->assertDatabaseHas('kitchen_tickets', [
      'id' => $ticket->id,
      'status' => KitchenTicketStatus::PROCESSING->value
    ]);

    $this->assertDatabaseHas('order_items', [
      'id' => $orderItem->id,
      'status' => OrderItemStatus::ACCEPTED->value
    ]);
  }

  #[Test]
  #[TestDox('Gửi thông báo cho nhân viên phục vụ khi món đã sẵn sàng')]
  public function test_it_can_notify_waiters()
  {
    Event::fake();


    $ticket = KitchenTicket::factory()->create(['status' => KitchenTicketStatus::WAITING]);

    // Tạo 2 nhân viên phục vụ và gán role bằng Spatie
    $waiters = User::factory()->count(2)->create(['is_active' => true, 'last_seen_at' => now()]);
    $waiters->each(fn($user) => $user->assignRole(UserRole::WAITER));

    $this->kitchenService->notifyWaiter($ticket->id);

    Event::assertDispatched(KitchenOrderReady::class);
  }
}
