<?php

namespace App\DTO;

class ValidationResult
{
  public bool $success;
  public string $message;

  public function __construct(bool $success, string $message)
  {
    $this->success = $success;
    $this->message = $message;
  }

  public static function success(string $message): self
  {
    return new self(true, $message);
  }

  public static function fail(string $message): self
  {
    return new self(false, $message);
  }
}
