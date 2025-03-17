<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
  use HasFactory;

  protected $fillable = ['name', 'branch_id', 'note'];


  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  public function tablesAndRooms()
  {
    return $this->hasMany(TableAndRoom::class, 'area_id');
  }
}
