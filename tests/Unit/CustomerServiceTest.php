<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerServiceTest extends TestCase
{
  use RefreshDatabase; // Tự động reset database trước mỗi test

  protected $customerService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->customerService = new CustomerService();
  }

  /**
   * Test tạo khách hàng mới
   */
  public function test_create_customer()
  {
    $customerData = [
      'name' => 'Nguyễn Văn A',
      'email' => 'a@example.com',
      'phone' => '0987654321',
      'address' => '123 Đường ABC',
      'dob' => '1990-01-01',
    ];

    $customer = $this->customerService->createCustomer($customerData);

    $this->assertDatabaseHas('customers', $customerData);
    $this->assertInstanceOf(Customer::class, $customer);
  }

  /**
   * Test cập nhật thông tin khách hàng
   */
  public function test_update_customer()
  {
    $customer = Customer::factory()->create();

    $updatedData = ['name' => 'Nguyễn Văn B'];
    $updatedCustomer = $this->customerService->updateCustomer($customer->id, $updatedData);

    $this->assertEquals('Nguyễn Văn B', $updatedCustomer->name);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Nguyễn Văn B']);
  }

  /**
   * Test tìm kiếm khách hàng theo số điện thoại
   */
  public function test_find_customer_by_phone()
  {
    $customer = Customer::factory()->create(['phone' => '0987654321']);

    $foundCustomer = $this->customerService->findCustomer('0987654321');

    $this->assertNotNull($foundCustomer);
    $this->assertEquals('0987654321', $foundCustomer->phone);
  }

  /**
   * Test tìm kiếm khách hàng theo email
   */
  public function test_find_customer_by_email()
  {
    $customer = Customer::factory()->create(['email' => 'test@example.com']);

    $foundCustomer = $this->customerService->findCustomer('test@example.com');

    $this->assertNotNull($foundCustomer);
    $this->assertEquals('test@example.com', $foundCustomer->email);
  }
}
