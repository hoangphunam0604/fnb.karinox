<?php

namespace App\Enums;

enum KitchenTicketStatus: string
{
  case WAITING = 'waiting';
  case PROCESSING = 'processing';
  case COMPLETED = 'completed';
  case CANCELLED = 'cancelled';
}
