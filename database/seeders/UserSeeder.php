<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $user = User::firstOrCreate(
        ['name' => 'User'],
        ['email' => 'user@example.com'],
        ['password' => bcrypt('user')],
    );
    $user->assignRole('user');
    $admin = User::firstOrCreate(
        ['name' => 'Admin'],
        ['email' => 'admin@example.com'],
        ['password' => bcrypt('admin')],
    );
    $admin->assignRole('admin');
  }
}
