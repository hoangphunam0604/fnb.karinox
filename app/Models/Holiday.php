<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
  protected $fillable = [
    'name',
    'date',
    'is_lunar',
    'description',
    'is_recurring',
  ];

  protected $casts = [
    'date' => 'date',
    'is_lunar' => 'boolean',
    'is_recurring' => 'boolean',
  ];
}
