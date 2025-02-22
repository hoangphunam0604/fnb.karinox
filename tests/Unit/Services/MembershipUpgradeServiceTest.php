<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\MembershipLevel;
use App\Models\Customer;
use App\Services\MembershipUpgradeService;

use PHPUnit\Framework\Attributes\Test;

class MembershipUpgradeServiceTest extends TestCase
{
  use RefreshDatabase;

  protected MembershipUpgradeService $upgradeService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->upgradeService = new MembershipUpgradeService();
  }

  #[Test]
  public function it_can_upgrade_customer_membership_and_save_reward()
  {
    $customer = Customer::factory()->create();
    $oldLevel = MembershipLevel::factory()->create();
    $newLevel = MembershipLevel::factory()->create(['upgrade_reward_content' => 'Miễn phí 1 ly cà phê']);

    $customer->update(['membership_level_id' => $oldLevel->id]);

    $history = $this->upgradeService->upgradeMembership($customer, $newLevel);

    $this->assertDatabaseHas('membership_upgrade_histories', [
      'customer_id' => $customer->id,
      'new_membership_level_id' => $newLevel->id,
      'reward_claimed' => false,
      'upgrade_reward_content' => 'Miễn phí 1 ly cà phê',
    ]);
  }

  #[Test]
  public function it_does_not_allow_duplicate_upgrades()
  {
    $this->expectException(\Exception::class);

    $customer = Customer::factory()->create();
    $newLevel = MembershipLevel::factory()->create();

    $this->upgradeService->upgradeMembership($customer, $newLevel);
    $this->upgradeService->upgradeMembership($customer, $newLevel); // Gây lỗi
  }

  #[Test]
  public function it_can_claim_reward_once()
  {
    $customer = Customer::factory()->create();
    $newLevel = MembershipLevel::factory()->create();

    $history = $this->upgradeService->upgradeMembership($customer, $newLevel);

    $this->upgradeService->claimReward($history);

    $this->assertTrue($history->refresh()->reward_claimed);
  }

  #[Test]
  public function it_does_not_allow_duplicate_rewards()
  {
    $this->expectException(\Exception::class);

    $customer = Customer::factory()->create();
    $newLevel = MembershipLevel::factory()->create();

    $history = $this->upgradeService->upgradeMembership($customer, $newLevel);

    $this->upgradeService->claimReward($history);
    $this->upgradeService->claimReward($history); // Gây lỗi
  }
}
