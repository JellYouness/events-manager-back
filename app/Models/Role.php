<?php

namespace App\Models;

class Role extends BaseModel
{
  public static $cacheKey = 'roles';
  protected $fillable = [
    'name',
  ];

  public function users()
  {
    return $this->belongsToMany(User::class, 'users_roles');
  }

  public function permissions()
  {
    return $this->belongsToMany(Permission::class, 'roles_permissions');
  }

  public function hasPermission($permissionName)
  {
    return $this->permissions->contains('name', $permissionName);
  }

  public function givePermission($permissionName)
  {
    $permission = Permission::where('name', $permissionName)->first();
    if (!$permission) {
      throw new \Exception("The permission {$permissionName} does not exist : impossible to give it to the role {$this->name}");
    }
    $this->permissions()->save($permission);
  }
}
