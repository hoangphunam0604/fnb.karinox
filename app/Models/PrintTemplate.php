<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
  use HasFactory;
  protected $fillable = [
    'type',
    'name',
    'description',
    'content',
    'is_default',
    'is_active',
  ];
}
