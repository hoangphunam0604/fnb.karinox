<?php

namespace Tests\Unit\Jobs\Tasks;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Jobs\Tasks\AnnualPointsAndMembershipProcess;

/**
 * @testdox Kiểm tra xử lý reset điểm và cập nhật cấp độ thành viên
 */
class AnnualPointsAndMembershipProcessTest extends TestCase
{
  use RefreshDatabase;

  /**
   * @testdox Reset điểm 
   * @test
   */
  public function it_resets_points()
  {
    // Tạo cấp độ thành viên
    $silver = MembershipLevel::factory()->create(['name' => 'Silver', 'min_spent' => 100000, 'rank' => 1]);

    // Tạo khách hàng
    $customer = Customer::factory()->create([
      'loyalty_points' => 600000, // Điểm hiện tại
      'reward_points' => 5000,
      'used_reward_points' => 2000,
      'membership_level_id' => $silver->id, // Ban đầu là Silver
    ]);
    // Chạy Job
    $job = new AnnualPointsAndMembershipProcess([$customer->id]);
    $job->handle();

    // Kiểm tra dữ liệu đã reset đúng
    $customer->refresh();

    //Xác nhận điểm đã được reset
    $this->assertEquals(0, $customer->loyalty_points);
    $this->assertEquals(0, $customer->reward_points);
    $this->assertEquals(0, $customer->used_reward_points);
  }

  /**
   * @testdox Không thay đổi hạng nếu điểm tích lũy vẫn đủ duy trì cấp bậc hiện tại
   * @test
   */
  public function it_does_not_change_membership_if_loyalty_points_are_sufficient_to_maintain_level()
  {
    $gold = MembershipLevel::factory()->create(['name' => 'Gold', 'min_spent' => 500000, 'rank' => 2]);
    // Tạo cấp độ cao nhất
    $diamond = MembershipLevel::factory()->create(['name' => 'Diamond', 'min_spent' => 1000000, 'rank' => 3]);

    // Tạo khách hàng có điểm đủ để giữ hạng Gold nhưng không đủ để lên Diamond
    $customer = Customer::factory()->create([
      'loyalty_points' => 550000, // Vừa đủ giữ Gold nhưng không đủ lên hạng cao hơn
      'reward_points' => 5000,
      'used_reward_points' => 2000,
      'membership_level_id' => $gold->id, // Đang ở hạng Gold
    ]);

    // Chạy Job
    $job = new AnnualPointsAndMembershipProcess([$customer->id]);
    $job->handle();

    // Kiểm tra khách hàng vẫn giữ nguyên hạng Gold
    $customer->refresh();
    $this->assertEquals($gold->id, $customer->membership_level_id);
  }

  /**
   * @testdox Nếu điểm tích luỹ không đủ trụ hạng sẽ bị tụt hạng
   * @test
   */
  public function it_downgrades_membership_if_loyalty_points_are_not_sufficient()
  {
    // Tạo cấp độ thành viên
    $silver = MembershipLevel::factory()->create(['name' => 'Silver', 'min_spent' => 100000, 'max_spent' => 4999999, 'rank' => 1]);
    $gold = MembershipLevel::factory()->create(['name' => 'Gold', 'min_spent' => 500000, 'rank' => 2]);

    // Tạo khách hàng ở hạng Gold nhưng không đủ điểm tích lũy để duy trì
    $customer = Customer::factory()->create([
      'loyalty_points' => 100000, // Chỉ đủ hạng Silver
      'reward_points' => 5000,
      'used_reward_points' => 2000,
      'membership_level_id' => $gold->id, // Ban đầu là Gold
    ]);

    // Chạy Job
    $job = new AnnualPointsAndMembershipProcess([$customer->id]);
    $job->handle();

    // Kiểm tra khách hàng bị tụt xuống Silver
    $customer->refresh();
    $this->assertEquals($silver->id, $customer->membership_level_id);
  }

  /**
   * @testdox Nếu điểm tích luỹ có thể tăng hạng thì tăng hạng
   * @test
   */
  public function it_upgrades_membership_if_loyalty_points_are_sufficient()
  {
    // Tạo cấp độ thành viên
    $silver = MembershipLevel::factory()->create(['name' => 'Silver', 'min_spent' => 100000, 'rank' => 1]);
    $gold = MembershipLevel::factory()->create(['name' => 'Gold', 'min_spent' => 500000, 'rank' => 2]);

    // Tạo khách hàng đang ở hạng Silver nhưng có đủ điểm để lên Gold
    $customer = Customer::factory()->create([
      'loyalty_points' => 600000, // Đủ để lên Gold
      'reward_points' => 5000,
      'used_reward_points' => 2000,
      'membership_level_id' => $silver->id, // Ban đầu là Silver
    ]);

    // Chạy Job
    $job = new AnnualPointsAndMembershipProcess([$customer->id]);
    $job->handle();

    // Kiểm tra khách hàng được nâng hạng lên Gold
    $customer->refresh();
    $this->assertEquals($gold->id, $customer->membership_level_id);
  }


  /**
   * @testdox Nếu thứ hạng đang ở cao nhất thì không thay đổi thứ hạng
   * @test
   */
  public function it_does_not_change_rank_if_already_at_highest_level()
  {
    // Tạo cấp độ cao nhất
    $diamond = MembershipLevel::factory()->create(['name' => 'Diamond', 'min_spent' => 1000000, 'rank' => 3]);

    // Tạo khách hàng đã ở hạng Diamond
    $customer = Customer::factory()->create([
      'loyalty_points' => 2000000, // Vượt mức cần thiết
      'reward_points' => 10000,
      'used_reward_points' => 5000,
      'membership_level_id' => $diamond->id,
    ]);

    // Chạy Job
    $job = new AnnualPointsAndMembershipProcess([$customer->id]);
    $job->handle();

    // Kiểm tra khách hàng vẫn giữ nguyên hạng Diamond
    $customer->refresh();
    $this->assertEquals($diamond->id, $customer->membership_level_id);
  }
  /**
   * @testdox Xử lý nhiều khách hàng trong một lần chạy
   * @test
   */
  public function it_processes_multiple_customers_in_one_job()
  {
    // Tạo cấp độ thành viên
    $silver = MembershipLevel::factory()->create(['name' => 'Silver', 'min_spent' => 100000, 'rank' => 1]);
    $gold = MembershipLevel::factory()->create(['name' => 'Gold', 'min_spent' => 500000, 'rank' => 2]);

    // Tạo 3 khách hàng
    $customers = Customer::factory()->count(3)->create([
      'loyalty_points' => 600000,
      'reward_points' => 5000,
      'used_reward_points' => 2000,
      'membership_level_id' => $silver->id,
    ]);

    // Chạy Job với danh sách khách hàng
    $job = new AnnualPointsAndMembershipProcess($customers->pluck('id')->toArray());
    $job->handle();

    // Kiểm tra tất cả khách hàng đã reset đúng
    foreach ($customers as $customer) {
      $customer->refresh();
      $this->assertEquals(0, $customer->loyalty_points);
      $this->assertEquals(0, $customer->reward_points);
      $this->assertEquals(0, $customer->used_reward_points);
      $this->assertEquals($gold->id, $customer->membership_level_id);
    }
  }
}
