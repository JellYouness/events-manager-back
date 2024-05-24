<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    $seeders = [
      PermissionSeeder::class,
      CrudPermissionSeeder::class,
    ];
    if (env('APP_ENV') !== 'prod') {
      $seeders = array_merge($seeders, [
        UserSeeder::class,
      ]);
    }
    $this->call($seeders);
  }
}
