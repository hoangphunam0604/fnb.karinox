<?php

namespace Tests\Feature;

use App\Jobs\ResetMembershipPoints;
use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * php artisan test --filter=ResetMembershipPointsTest
 */
class ResetMembershipPointsTest extends TestCase
{
  use RefreshDatabase;

  public function setUp(): void
  {
    parent::setUp();

    // Tạo các cấp độ thành viên
    MembershipLevel::factory()->create(['rank' => 1, 'name' => 'Bronze', 'min_spent' => 0, 'max_spent' => 499]);
    MembershipLevel::factory()->create(['rank' => 2, 'name' => 'Silver', 'min_spent' => 500, 'max_spent' => 999]);
    MembershipLevel::factory()->create(['rank' => 3, 'name' => 'Gold', 'min_spent' => 1000, 'max_spent' => 1999]);
    MembershipLevel::factory()->create(['rank' => 4, 'name' => 'Platinum', 'min_spent' => 2000, 'max_spent' => null]);
  }

  /** @test */
  public function it_resets_loyalty_and_reward_points()
  {
    $customer = Customer::factory()->create([
      'name' => "Nam",
      'loyalty_points' => 1200, // Điểm cao nhất trong năm qua
      'reward_points' => 300,
      'used_reward_points' => 150,
    ]);

    // Đẩy Job vào hàng đợi
    ResetMembershipPoints::dispatch();

    // Lấy dữ liệu mới từ database
    $customer->refresh();

    // Kiểm tra điểm loyalty, reward và used_reward_points phải về 0
    $this->assertEquals(0, $customer->loyalty_points);
    $this->assertEquals(0, $customer->reward_points);
    $this->assertEquals(0, $customer->used_reward_points);
  }

  /** @test */
  public function it_dispatches_reset_membership_points_job()
  {
    Queue::fake();

    ResetMembershipPoints::dispatch();

    Queue::assertPushed(ResetMembershipPoints::class);
  }
}
