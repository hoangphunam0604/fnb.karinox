<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      UserSeeder::class,
      SystemSettingSeeder::class,
      AreasAndTablesSeeder::class,
      MembershipLevelSeeder::class,
      MenuSeeder::class,
      VoucherSeeder::class,
      HolidaySeeder::class,
      PrintTemplateSeeder::class,
    ]);
  }
}
