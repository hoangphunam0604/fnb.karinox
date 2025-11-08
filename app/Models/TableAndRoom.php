<?php

namespace App\Models;

use App\Enums\TableAndRoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableAndRoom extends Model
{
  use HasFactory;

  protected $table = 'tables_and_rooms';

  protected $fillable = ['name', 'area_id', 'branch_id', 'capacity', 'note', 'status'];

  protected $casts = [
    'capacity' => 'integer',
    'status' => TableAndRoomStatus::class,
  ];


  public function area()
  {
    return $this->belongsTo(Area::class);
  }
  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }
  /**
   * Scope tìm kiếm phòng/bàn theo trạng thái
   */
  public function scopeByStatus($query, $status)
  {
    return $query->where('status', $status);
  }

  /**
   * Kiểm tra phòng/bàn có sẵn không
   */
  public function isAvailable()
  {
    return $this->status === TableAndRoomStatus::AVAILABLE;
  }
}
