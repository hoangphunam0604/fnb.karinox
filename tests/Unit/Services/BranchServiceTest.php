<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BranchServiceTest extends TestCase
{
  use RefreshDatabase; // Reset database trước mỗi test

  protected $branchService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->branchService = new \App\Services\BranchService();
  }

  /**
   * Test tạo chi nhánh mới
   */
  public function test_create_branch()
  {
    $branchData = [
      'name' => 'Chi nhánh Hà Nội',
      'address' => '123 Nguyễn Trãi',
      'phone_number' => '0123456789',
    ];

    $branch = $this->branchService->createBranch($branchData);

    $this->assertDatabaseHas('branches', $branchData);
    $this->assertInstanceOf(Branch::class, $branch);
  }

  /**
   * Test cập nhật thông tin chi nhánh
   */
  public function test_update_branch()
  {
    $branch = Branch::factory()->create();

    $updatedData = ['name' => 'Chi nhánh TP.HCM'];
    $updatedBranch = $this->branchService->updateBranch($branch->id, $updatedData);

    $this->assertEquals('Chi nhánh TP.HCM', $updatedBranch->name);
    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Chi nhánh TP.HCM']);
  }

  /**
   * Test tìm kiếm chi nhánh theo tên
   */
  public function test_find_branch_by_name()
  {
    $branch = Branch::factory()->create(['name' => 'Chi nhánh Đà Nẵng']);

    $foundBranch = $this->branchService->findBranch('Đà Nẵng');

    $this->assertNotNull($foundBranch);
    $this->assertEquals('Chi nhánh Đà Nẵng', $foundBranch->name);
  }

  /**
   * Test xóa chi nhánh mà KHÔNG cần kiểm tra sản phẩm hoặc đơn hàng
   */
  public function test_delete_branch_without_checking_products_or_orders()
  {
    $branch = Branch::factory()->create();

    $this->branchService->deleteBranch($branch->id);

    $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
  }
}
