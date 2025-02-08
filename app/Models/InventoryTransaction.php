<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InventoryTransaction extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'reference_id',
    'transaction_type',
    'branch_id',
    'destination_branch_id',
    'note'
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($transaction) {
      $transaction->user_id = Auth::id();
    });
  }
}
