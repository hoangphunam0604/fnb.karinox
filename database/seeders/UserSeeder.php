<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {

    Role::firstOrCreate(['name' => UserRole::ADMIN, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::MANAGER, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::WAITER, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::KITCHEN_STAFF, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::CASHIER, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::DELIVERY_STAFF, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::INVENTORY_STAFF, 'guard_name' => 'web']);

    // Tạo tài khoản admin
    $admin = User::updateOrCreate([
      'username' => 'karinox_admin',
    ], [
      'fullname' => 'Karinox Admin',
      'password' => Hash::make('karinox_admin'),
    ]);
    $admin->assignRole(UserRole::ADMIN);
  }
}
