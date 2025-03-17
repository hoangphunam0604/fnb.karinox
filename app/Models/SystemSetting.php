<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
  use HasFactory;

  protected $fillable = ['key', 'value'];

  public static function getValue(string $key, $default = null)
  {
    return self::where('key', $key)->value('value') ?? $default;
  }
}
