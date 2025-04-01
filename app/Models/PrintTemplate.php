<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
  use HasFactory;
  protected $fillable = [
    'branch_id',
    'type',
    'name',
    'description',
    'content',
    'is_default',
    'is_active',
  ];

  protected $casts = [
    'is_default'  =>  'boolean',
    'is_active' =>  'boolean',
  ];

  public function branch()
  {
    return $this->belongsTo(Branch::class)->withDefault();
  }
}
