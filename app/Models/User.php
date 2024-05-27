<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use DB;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
  use HasApiTokens;
  use Notifiable;
  use Authenticatable;
  use Authorizable;
  use CanResetPassword;
  use MustVerifyEmail;

  public static $cacheKey = 'users';
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'roles',
    'permissions',
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  protected $appends = [
    'rolesNames',
    'permissionsNames'
  ];

  public function getRolesNamesAttribute()
  {
    $rolesNames = $this->roles->pluck('name')->all();
    sort($rolesNames);
    return $rolesNames;
  }

  public function getPermissionsNamesAttribute()
  {
    return $this->allPermissions()->pluck('name')->all();
  }

  protected static function booted()
  {
    parent::booted();
    static::created(function ($user) {
      $user->givePermission('users.' . $user->id . '.read');
      $user->givePermission('users.' . $user->id . '.update');
      $user->givePermission('users.' . $user->id . '.delete');
    });
    static::deleted(function ($user) {
      $permissions = Permission::where('name', 'like', 'users.' . $user->id . '.%')->get();
      DB::table('users_permissions')->whereIn('permission_id', $permissions->pluck('id'))->delete();
      Permission::destroy($permissions->pluck('id'));
    });
  }

  public function roles()
  {
    return $this->belongsToMany(Role::class, 'users_roles');
  }

  public function hasRole($roleName)
  {
    return $this->roles->contains('name', $roleName);
  }

  public function assignRole($roleName)
  {
    $role = Role::where('name', $roleName)->first();
    $this->roles()->syncWithoutDetaching([$role->id]);
  }

  public function syncRoles($roleNames)
  {
    $roles = Role::whereIn('name', $roleNames)->get();
    $this->roles()->sync($roles);
  }

  public function events()
  {
    return $this->hasMany(Event::class);
  }

  public function usersEvents()
  {
    return $this->belongsToMany(Event::class, 'users_events');
  }

  public function permissions()
  {
    return $this->belongsToMany(Permission::class, 'users_permissions');
  }

  public function allPermissions()
  {
    $permissions = $this->permissions;
    foreach ($this->roles as $role) {
      $permissions = $permissions->merge($role->permissions);
    }
    return $permissions;
  }

  public function hasPermissionName($permissionName)
  {
    return $this->allPermissions()->contains('name', $permissionName);
  }

  public function hasPermission($entityName, $action, $entityId = null)
  {
    $permissionName = $entityName . ".$action";
    if ($this->hasPermissionName($permissionName)) {
      return true;
    }
    $permissionName = $entityName . '.*';
    if ($this->hasPermissionName($permissionName)) {
      return true;
    }
    if ($entityId !== null) {
      $permissionName = $entityName . ".$entityId.$action";
      if ($this->hasPermissionName($permissionName)) {
        return true;
      }
    }
    return false;
  }

  public function givePermission($permissionName)
  {
    $permission = Permission::where('name', $permissionName)->first();
    if (!$permission) {
      $permission = Permission::create(['name' => $permissionName]);
    }
    $this->permissions()->save($permission);
  }

  public function rules($id = null)
  {
    $id = $id ?? request()->route('id');
    $rules = [
      'name' => 'required|string',
      'role' => 'required|exists:roles,name',
      'email' => 'required|email|unique:users,email',
      'password' => 'required|string',
    ];
    if ($id !== null) {
      $rules['email'] .= ',' . $id;
      $rules['password'] = 'nullable|string';
    }
    return $rules;
  }

  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPasswordNotification($token));
  }
}
