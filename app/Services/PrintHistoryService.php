<?php

namespace App\Services;

use App\Models\PrintHistory;
use Illuminate\Database\Eloquent\Model;

class PrintHistoryService extends BaseService
{
  protected function model(): Model
  {
    return new PrintHistory();
  }
  public function confirmPrint(PrintHistory $printHistory): void
  {
    $printHistory->markAsPrinted();
  }
  public function markPrintFailed(PrintHistory $printHistory): void
  {
    $printHistory->markAsFailed();
  }

  protected function applySearch($query, array $params)
  {
    if (!empty($params['type'])) {
      $query->where('type', $params['type']);
    }
    return $query;
  }
}
