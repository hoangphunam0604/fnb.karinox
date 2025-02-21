<?php

namespace Tests\Unit\Services;

use App\Services\SystemSettingService;
use App\Services\TaxService;
use Mockery;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
  protected $taxService;
  protected $systemSettingService;

  protected function setUp(): void
  {
    parent::setUp();


    $this->systemSettingService = Mockery::spy(SystemSettingService::class);
    $this->app->instance(SystemSettingService::class, $this->systemSettingService);
    $this->taxService = app(TaxService::class);
  }


  public function it_calculates_tax_correctly_with_default_tax_rate()
  {
    $this->systemSettingService->shouldReceive('getTaxRate')
      ->once()
      ->andReturn(10);

    $result = $this->taxService->calculateTax(110000);

    $this->assertEquals(10000, $result['tax_amount']);
    $this->assertEquals(100000, $result['total_price_without_vat']);
  }


  public function it_calculates_tax_correctly_with_custom_tax_rate()
  {
    $result = $this->taxService->calculateTax(108000, 8);

    $this->assertEquals(8000, $result['tax_amount']);
    $this->assertEquals(100000, $result['total_price_without_vat']);
  }


  public function it_returns_zero_tax_if_tax_rate_is_zero()
  {
    $result = $this->taxService->calculateTax(100000, 0);

    $this->assertEquals(0, $result['tax_amount']);
    $this->assertEquals(100000, $result['total_price_without_vat']);
  }
}
