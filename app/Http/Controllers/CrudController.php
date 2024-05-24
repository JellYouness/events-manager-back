<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class CrudController extends Controller
{
  protected $table;
  protected $modelClass;
  protected $restricted = ['create', 'read_one', 'read_all', 'update', 'delete'];

  protected function model()
  {
    return app($this->modelClass);
  }

  public function createOne(Request $request)
  {
    if (in_array('create', $this->restricted)) {
      $user = $request->user();
      if (!$user->hasPermission($this->table, 'create')) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }
    $validated = $request->validate(app($this->modelClass)->rules());

    $model = $this->model()->create($validated);

    if (method_exists($this, 'afterCreateOne')) {
      $this->afterCreateOne($model, $request);
    }

    return response()->json([
      'success' => true,
      'data' => ['item' => $model],
      'message' => __(Str::of($this->table)->replace('_', '-') . '.created')
    ]);
  }

  public function readOne($id, Request $request)
  {
    if (in_array('read_one', $this->restricted)) {
      $user = $request->user();
      if (!$user->hasPermission($this->table, 'read', $id)) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }

    // Retrieve item from cache if exists, otherwise retrieve from database
    if (property_exists($this->modelClass, 'cacheKey')) {
      $cacheKey = $this->modelClass::$cacheKey;
      if (!Cache::has($cacheKey)) {
        $items = $this->model()->all();
        Cache::put($cacheKey, $items);
      } else {
        $items = Cache::get($cacheKey);
      }
    }
    $item = $items->firstWhere('id', $id);

    if (!$item) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
    }

    if (method_exists($this, 'afterReadOne')) {
      $this->afterReadOne($item, $request);
    }

    return response()->json([
      'success' => true,
      'data' => ['item' => $item],
    ]);
  }

  public function readAll(Request $request)
  {
    $user = $request->user();

    if (in_array('read_all', $this->restricted)) {
      if (!$user->hasPermission($this->table, 'read') && !$user->hasPermission($this->table, 'read_own')) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }

    // Retrieve all items from cache if exists, otherwise retrieve from database
    if (property_exists($this->modelClass, 'cacheKey')) {
      $cacheKey = $this->modelClass::$cacheKey;
      if (!Cache::has($cacheKey)) {
        $items = $this->model()->all();
        Cache::put($cacheKey, $items);
      } else {
        $items = Cache::get($cacheKey);
      }
    }

    // If user has permission to read own items only, then filter the items
    if (!$user->hasPermission($this->table, 'read')) {
      $items = $items->filter(function ($model) use ($user) {
        return $user->hasPermission($this->table, 'read', $model->id);
      })->values();
    }

    if (method_exists($this, 'afterReadAll')) {
      $this->afterReadAll($items);
    }

    return response()->json([
      'success' => true,
      'data' => ['items' => $items],
    ]);
  }

  public function updateOne($id, Request $request)
  {
    if (in_array('update', $this->restricted)) {
      $user = $request->user();
      if (!$user->hasPermission($this->table, 'update', $id)) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }

    $validated = $request->validate(app($this->modelClass)->rules($id));

    $model = $this->model()->find($id);

    if (!$model) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
    }

    $model->update($validated);

    if (method_exists($this, 'afterUpdateOne')) {
      $this->afterUpdateOne($model, $request);
    }

    return response()->json([
      'success' => true,
      'data' => ['item' => $model],
      'validated' => $validated,
      'message' => __(Str::of($this->table)->replace('_', '-') . '.updated')
    ]);
  }

  public function deleteOne($id, Request $request)
  {
    if (in_array('delete', $this->restricted)) {
      $user = $request->user();
      if (!$user->hasPermission($this->table, 'delete', $id)) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }

    $model = $this->model()->find($id);

    if (!$model) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
    }

    $model->delete();

    // Delete linked uploads
    $rules = app($this->modelClass)->rules($id);
    foreach ($rules as $key => $value) {
      $isUpload = false;
      if (is_array($value)) {
        if (in_array('exists:uploads,id', $value)) {
          $isUpload = true;
        }
      } elseif (str_contains($value, 'exists:uploads,id')) {
        $isUpload = true;
      }
      if ($isUpload) {
        $upload = Upload::find($model->$key);
        if ($upload) {
          $path = $upload->path;
          if ($path) {
            Storage::disk('cloud')->delete($path);
          }
          $upload->delete();
        }
      }
    }

    if (method_exists($this, 'afterDeleteOne')) {
      $this->afterDeleteOne($model, $request);
    }

    return response()->json([
      'success' => true,
      'message' => __(Str::of($this->table)->replace('_', '-') . '.deleted')
    ]);
  }
}
