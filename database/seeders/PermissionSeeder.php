<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Define roles
    $userRole = $this->createRole('user');
    $adminRole = $this->createRole('admin');

    // Define permissions
    $this->createScopePermissions('users', ['create', 'read', 'update', 'delete']);

    // Assign permissions to roles
    $this->assignScopePermissionsToRole($adminRole, 'users', ['create', 'read', 'update', 'delete']);
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
