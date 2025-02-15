<?php

namespace Tests\Unit\Jobs\Tasks;

use App\Jobs\Tasks\AnnualPointsAndMembershipReset;
use App\Jobs\Tasks\AnnualPointsAndMembershipProcess;
use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * @testdox Kiểm tra reset điểm hằng năm và dispatch process
 */
class AnnualPointsAndMembershipResetTest extends TestCase
{
  use RefreshDatabase;

  /**
   * @testdox Dispatch job xử lý reset điểm cho từng batch khách hàng
   * @test
   */
  public function it_dispatches_annual_points_and_membership_process()
  {

    Bus::fake();

    // Tạo 10 khách hàng giả lập
    Customer::factory()->count(10)->create();

    // Chạy job Reset
    $job = new AnnualPointsAndMembershipReset();
    $job->handle();

    // Kiểm tra job Process đã được dispatch
    Bus::assertDispatched(AnnualPointsAndMembershipProcess::class);
  }
}
