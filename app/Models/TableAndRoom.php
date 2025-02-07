<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableAndRoom extends Model
{
  use HasFactory;
  protected $fillable = ['name', 'area_id', 'capacity', 'notes', 'is_active'];

  public function area()
  {
    return $this->belongsTo(Area::class)->withDefault([
      'name' => 'Không có khu vực'
    ]);
  }
}
