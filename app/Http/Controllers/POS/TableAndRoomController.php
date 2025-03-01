<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BranchService;
use App\Services\TableAndRoomService;

class TableAndRoomController extends Controller
{
  protected $tableAndRoomService;

  public function __construct(TableAndRoomService $tableAndRoomService)
  {
    $this->tableAndRoomService = $tableAndRoomService;
  }

  public function list()
  {
    $branchId = session('current_branch');
    $branch = $this->tableAndRoomService->listTablesAndRooms($branchId);
    return response()->json($branch);
  }
}
