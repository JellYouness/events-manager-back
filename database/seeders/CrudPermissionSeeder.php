<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CrudPermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    /*
      Here, include project specific permissions. E.G.:
      $this->createScopePermissions('interests', ['create', 'read', 'update', 'delete', 'import', 'export']);
      $this->createScopePermissions('games', ['create', 'read', 'read_own', 'update', 'delete']);

      $adminRole = Role::where('name', 'admin')->first();
      $this->assignScopePermissionsToRole($adminRole, 'interests', ['create', 'read', 'update', 'delete', 'import', 'export']);
      $this->assignScopePermissionsToRole($adminRole, 'games', ['create', 'read', 'read_own', 'update', 'delete']);

      $advertiserRole = Role::where('name', 'advertiser')->first();
      $this->assignScopePermissionsToRole($advertiserRole, 'interests', ['read']);
      $this->assignScopePermissionsToRole($advertiserRole, 'games', ['create', 'read_own']);
    */
  }

  public function createRole(string $name): Role
  {
    $role = Role::firstOrCreate(['name' => $name]);
    return $role;
  }
  public function createScopePermissions(string $scope, array $permissions): void
  {
    foreach ($permissions as $permission) {
      Permission::firstOrCreate(['name' => $scope . '.' . $permission]);
    }
  }
  public function assignScopePermissionsToRole(Role $role, string $scope, array $permissions): void
  {
    foreach ($permissions as $permission) {
      $permissionName = $scope . '.' . $permission;

      if (!$role->hasPermission($permissionName)) {
        $role->givePermission($permissionName);
      }
    }
  }
}
