<?php

namespace App\Models;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'phone_number',
    'email',
    'address',
    'status',
  ];

  protected $casts = [
    'status'  => CommonStatus::class,
  ];
  public function products()
  {
    return $this->belongsToMany(Product::class, 'product_branches');
  }
}
