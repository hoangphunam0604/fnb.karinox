<?php
// app/Events/TableUpdated.php
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Table;

class TableUpdated implements ShouldBroadcast
{
  public $table;

  public function __construct(Table $table)
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
