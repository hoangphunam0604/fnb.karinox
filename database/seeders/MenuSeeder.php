<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{

  /**
   * Run the database seeds.
   */
  public function run(): void
  {

    Menu::query()->delete();
    Menu::create(['name' => 'Thực đơn', 'order' => 100]);
    Menu::create(['name' => 'Cà phê']);
    Menu::create(['name' => 'Ca cao']);
    Menu::create(['name' => 'Trà sữa']);
    Menu::create(['name' => 'Sữa chua']);
  }
}
