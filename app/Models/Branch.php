<?php

namespace App\Models;

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

  public function products()
  {
    return $this->belongsToMany(Product::class, 'product_branches');
  }
}
