<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
  use RefreshDatabase;

  protected CustomerService $customerService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->customerService = new CustomerService();
  }


  public function it_can_create_a_customer()
  {
    $data = [
      'fullname' => 'Nguyễn Văn A',
      'phone' => '0905123456',
      'email' => 'nguyenvana@example.com',
      'loyalty_card_number' => 'VIP001'
    ];

    $customer = $this->customerService->createCustomer($data);

    $this->assertDatabaseHas('customers', $data);
    $this->assertInstanceOf(Customer::class, $customer);
  }


  public function it_can_update_a_customer()
  {
    $customer = Customer::factory()->create([
      'fullname' => 'Trần Thị B',
      'phone' => '0911123456'
    ]);

    $updatedData = ['fullname' => 'Trần Thị C'];

    $updatedCustomer = $this->customerService->updateCustomer($customer->id, $updatedData);

    $this->assertEquals('Trần Thị C', $updatedCustomer->fullname);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'fullname' => 'Trần Thị C']);
  }


  public function it_can_delete_a_customer()
  {
    $customer = Customer::factory()->create([
      'fullname' => 'Lê Văn D'
    ]);

    $this->customerService->deleteCustomer($customer->id);

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
  }


  public function it_can_find_customer_by_phone_or_email_or_loyalty_card()
  {
    $customer = Customer::factory()->create([
      'fullname' => 'Phạm Minh E',
      'phone' => '0987654321',
      'email' => 'phamminhe@example.com',
      'loyalty_card_number' => 'VIP999'
    ]);

    $foundByPhone = $this->customerService->findCustomer('0987654321');
    $foundByEmail = $this->customerService->findCustomer('phamminhe@example.com');
    $foundByCard = $this->customerService->findCustomer('VIP999');

    $this->assertEquals($customer->id, $foundByPhone->id);
    $this->assertEquals($customer->id, $foundByEmail->id);
    $this->assertEquals($customer->id, $foundByCard->id);
  }


  public function it_can_get_customer_membership_level()
  {
    // Tạo hạng thành viên với hệ số nhân điểm thưởng
    $membershipLevel = MembershipLevel::factory()->create([
      'name' => 'VIP Gold',
      'reward_multiplier' => 1.5,
    ]);
    $customer = Customer::factory()->create([
      'fullname' => 'Hoàng Quốc F',
      'membership_level_id' => $membershipLevel->id
    ]);

    $customerMembershipLevel = $this->customerService->getCustomerMembershipLevel($customer->id);

    $this->assertEquals($membershipLevel->id, $customerMembershipLevel->id);
  }

  public function it_returns_next_level_if_available()
  {
    $level1 = MembershipLevel::factory()->create(['rank' => 1, 'name' => 'Silver', 'min_spent' => 100000]);
    $level2 = MembershipLevel::factory()->create(['rank' => 2, 'name' => 'Gold', 'min_spent' => 500000]);

    $customer = Customer::factory()->create([
      'membership_level_id' => $level1->id,
      'total_spent' => 200000, // Đã chi tiêu nhưng chưa đủ lên Gold
    ]);

    $nextLevel = $this->customerService->getNextLevel($customer);

    $this->assertNotNull($nextLevel);
    $this->assertEquals('Gold', $nextLevel->name);
  }

  public function it_returns_null_if_no_next_level()
  {
    $level = MembershipLevel::factory()->create(['rank' => 3, 'name' => 'Platinum', 'min_spent' => 1000000]);

    $customer = Customer::factory()->create([
      'membership_level_id' => $level->id,
      'total_spent' => 2000000, // Đã chi tiêu vượt mức nhưng không có cấp tiếp theo
    ]);

    $nextLevel = $this->customerService->getNextLevel($customer);

    $this->assertNull($nextLevel);
  }

  public function it_returns_points_needed_for_next_level()
  {
    $level1 = MembershipLevel::factory()->create(['rank' => 1, 'name' => 'Silver', 'min_spent' => 100000]);
    $level2 = MembershipLevel::factory()->create(['rank' => 2, 'name' => 'Gold', 'min_spent' => 500000]);

    $customer = Customer::factory()->create([
      'membership_level_id' => $level1->id,
      'total_spent' => 200000, // Đã chi tiêu nhưng chưa đủ lên Gold
    ]);

    $nextLevelInfo = $this->customerService->getNextMembershipLevel($customer);

    $this->assertNotNull($nextLevelInfo);
    $this->assertEquals('Gold', $nextLevelInfo['next_level']);
    $this->assertEquals(300000, $nextLevelInfo['points_needed']);
  }

  public function it_returns_null_for_next_level_if_no_more_upgrades()
  {
    $level = MembershipLevel::factory()->create(['rank' => 3, 'name' => 'Platinum', 'min_spent' => 1000000]);

    $customer = Customer::factory()->create([
      'membership_level_id' => $level->id,
      'total_spent' => 2000000, // Đã đạt mức cao nhất
    ]);

    $nextLevelInfo = $this->customerService->getNextMembershipLevel($customer);

    $this->assertNull($nextLevelInfo);
  }
}
