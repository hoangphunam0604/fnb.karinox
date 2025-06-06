<?php
// app/Events/TableUpdated.php
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Models\TableAndRoom;

class TableUpdated implements ShouldBroadcastNow
{
  public $table;

  public function __construct(TableAndRoom $table)
  {
    $this->table = $table;
  }

  public function broadcastOn()
  {
    return new Channel('tables-channel');
  }

  public function broadcastAs()
  {
    return 'table-updated';
  }
}
