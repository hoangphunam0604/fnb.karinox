<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
  use RefreshDatabase;

  protected SystemSettingService $systemSettingService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->systemSettingService = new SystemSettingService();
  }

  /** @test */
  public function it_can_get_a_setting_value()
  {
    SystemSetting::create([
      'key' => 'site_name',
      'value' => 'Karinox Coffee'
    ]);

    $this->assertEquals('Karinox Coffee', $this->systemSettingService->get('site_name'));
  }

  /** @test */
  public function it_returns_default_value_if_setting_not_found()
  {
    $this->assertEquals('default_value', $this->systemSettingService->get('non_existing_key', 'default_value'));
  }

  /** @test */
  public function it_can_set_a_new_system_setting()
  {
    $this->systemSettingService->set('currency', 'VND');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'currency',
      'value' => 'VND'
    ]);
  }

  /** @test */
  public function it_can_update_an_existing_system_setting()
  {
    SystemSetting::create([
      'key' => 'timezone',
      'value' => 'UTC'
    ]);

    $this->systemSettingService->set('timezone', 'Asia/Ho_Chi_Minh');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'timezone',
      'value' => 'Asia/Ho_Chi_Minh'
    ]);
  }

  /** @test */
  public function it_can_create_or_update_system_setting()
  {
    $this->systemSettingService->set('tax_rate', '10%');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'tax_rate',
      'value' => '10%'
    ]);

    $this->systemSettingService->set('tax_rate', '12%');

    $this->assertDatabaseHas('system_settings', [
      'key' => 'tax_rate',
      'value' => '12%'
    ]);
  }
}
