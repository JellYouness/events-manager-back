<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CrudController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends CrudController
{
  protected $table = 'users';
  protected $modelClass = User::class;

  public function createOne(Request $request)
  {
    $request->merge(['password' => Hash::make($request->password)]);
    return parent::createOne($request);
  }

  public function afterCreateOne($item, $request)
  {
    $item->syncRoles([$request->role]);
  }

  public function updateOne($id, Request $request)
  {
    if (isset($request->password) && !empty($request->password)) {
      $request->merge(['password' => Hash::make($request->password)]);
    } else {
      $request->request->remove('password');
    }
    return parent::updateOne($id, $request);
  }

  public function afterUpdateOne($item, $request)
  {
    $item->syncRoles([$request->role]);
  }
}
