<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MembershipLevel;
use App\Models\SystemSetting;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CustomerServiceTest extends TestCase
{
  use RefreshDatabase;

  protected CustomerService $customerService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->customerService = new CustomerService();
  }

  /** @test */
  public function it_can_create_a_customer()
  {
    $data = [
      'name' => 'Nguyễn Văn A',
      'phone' => '0905123456',
      'email' => 'nguyenvana@example.com',
      'loyalty_card_number' => 'VIP001'
    ];

    $customer = $this->customerService->createCustomer($data);

    $this->assertDatabaseHas('customers', $data);
    $this->assertInstanceOf(Customer::class, $customer);
  }

  /** @test */
  public function it_can_update_a_customer()
  {
    $customer = Customer::factory()->create([
      'name' => 'Trần Thị B',
      'phone' => '0911123456'
    ]);

    $updatedData = ['name' => 'Trần Thị C'];

    $updatedCustomer = $this->customerService->updateCustomer($customer->id, $updatedData);

    $this->assertEquals('Trần Thị C', $updatedCustomer->name);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Trần Thị C']);
  }

  /** @test */
  public function it_can_delete_a_customer()
  {
    $customer = Customer::factory()->create([
      'name' => 'Lê Văn D'
    ]);

    $this->customerService->deleteCustomer($customer->id);

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
  }

  /** @test */
  public function it_can_find_customer_by_phone_or_email_or_loyalty_card()
  {
    $customer = Customer::factory()->create([
      'name' => 'Phạm Minh E',
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

  /** @test */
  public function it_can_get_customer_membership_level()
  {
    // Tạo hạng thành viên với hệ số nhân điểm thưởng
    $membershipLevel = MembershipLevel::factory()->create([
      'name' => 'VIP Gold',
      'reward_multiplier' => 1.5,
    ]);
    $customer = Customer::factory()->create([
      'name' => 'Hoàng Quốc F',
      'membership_level_id' => $membershipLevel->id
    ]);

    $customerMembershipLevel = $this->customerService->getCustomerMembershipLevel($customer->id);

    $this->assertEquals($membershipLevel->id, $customerMembershipLevel->id);
  }

  /** @test */
  public function it_can_add_points_from_invoice()
  {
    $customer = Customer::factory()->create([
      'name' => 'Đặng Thị G',
      'loyalty_points' => 0,
      'reward_points' => 0
    ]);

    SystemSetting::create(['key' => 'point_conversion_rate', 'value' => '25000']);

    $invoice = Invoice::factory()->create([
      'total_amount' => 750000
    ]);

    $this->customerService->addPointsFromInvoice($customer->id, $invoice);

    $customer->refresh();

    $expectedPoints = floor(750000 / 25000);

    $this->assertEquals($expectedPoints, $customer->loyalty_points);
    $this->assertEquals($expectedPoints, $customer->reward_points);
  }

  /** @test */
  public function it_correctly_applies_bonus_multiplier_on_birthday()
  {
    // Tạo hạng thành viên với hệ số nhân điểm thưởng
    $membershipLevel = MembershipLevel::factory()->create([
      'name' => 'VIP Gold',
      'reward_multiplier' => 1.5,
    ]);

    // Tạo khách hàng với ngày sinh nhật là hôm nay và có hạng VIP Gold
    $customer = Customer::factory()->create([
      'name' => 'Ngô Văn H',
      'loyalty_points' => 0,
      'reward_points' => 0,
      'birthday' => Carbon::now()->format('Y-m-d'),
      'membership_level_id' => $membershipLevel->id,
    ]);

    SystemSetting::create(['key' => 'point_conversion_rate', 'value' => '25000']);

    $invoice = Invoice::factory()->create(['total_amount' => 600000]);

    $this->customerService->addPointsFromInvoice($customer->id, $invoice);

    $customer->refresh();

    $expectedPoints = floor(600000 / 25000);
    $multiplier = $membershipLevel->reward_multiplier;

    $this->assertEquals($expectedPoints, $customer->loyalty_points);
    $this->assertEquals($expectedPoints * $multiplier, $customer->reward_points);
  }

  /** @test */
  public function it_does_not_apply_bonus_multiplier_if_not_birthday()
  {
    // Tạo hạng thành viên với hệ số nhân điểm thưởng
    $membershipLevel = MembershipLevel::factory()->create([
      'name' => 'VIP Gold',
      'reward_multiplier' => 1.5,
    ]);
    $customer = Customer::factory()->create([
      'name' => 'Vũ Minh I',
      'loyalty_points' => 0,
      'reward_points' => 0,
      'birthday' => Carbon::now()->subDays(1)->format('Y-m-d'),
      'membership_level_id' => $membershipLevel->id,
    ]);

    SystemSetting::create(['key' => 'point_conversion_rate', 'value' => '25000']);

    $invoice = Invoice::factory()->create(['total_amount' => 500000]);

    $this->customerService->addPointsFromInvoice($customer->id, $invoice);

    $customer->refresh();

    $expectedPoints = floor(500000 / 25000);

    $this->assertEquals($expectedPoints, $customer->loyalty_points);
    $this->assertEquals($expectedPoints, $customer->reward_points);
  }


  /** @test */
  public function it_does_not_apply_birthday_bonus_if_customer_has_already_received_bonus_this_year()
  {
    // Tạo hạng thành viên với hệ số nhân điểm thưởng
    $membershipLevel = MembershipLevel::factory()->create([
      'name' => 'VIP Gold',
      'reward_multiplier' => 1.5,
    ]);
    $customer = Customer::factory()->create([
      'name' => 'Phan Văn K',
      'loyalty_points' => 0,
      'reward_points' => 0,
      'birthday' => Carbon::now()->format('Y-m-d'), // Sinh nhật cũ (đã qua 3 tháng)
      'membership_level_id' => $membershipLevel->id,
      'last_birthday_bonus_date' => Carbon::now()->subDays(3), // Đã nhận bonus từ 3 ngày trước
    ]);

    SystemSetting::create(['key' => 'point_conversion_rate', 'value' => '25000']);

    $invoice = Invoice::factory()->create(['total_amount' => 500000]);

    $this->customerService->addPointsFromInvoice($customer->id, $invoice);

    $customer->refresh();

    $expectedPoints = floor(500000 / 25000);

    // Vì đã nhận quà sinh nhật trước đó trong năm, không áp dụng nhân điểm nữa
    $this->assertEquals($expectedPoints, $customer->loyalty_points);
    $this->assertEquals($expectedPoints, $customer->reward_points);
  }
}
