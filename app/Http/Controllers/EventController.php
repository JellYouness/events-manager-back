<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EventController extends CrudController
{
    protected $table = 'events';
    protected $modelClass = Event::class;
    protected $rules = [
        'name' => 'required|string',
            'date' => 'required',
            'location' => 'required|string',
            'description' => 'required|string',
            'max_participants' => 'required|integer',
            'image' => 'nullable|string',
            'is_canceled' => 'boolean',
            'user_id' => 'integer'
    ];

    public function createOne(Request $request)
  {
    $user = $request->user();
    $request->validate($this->rules);
    
    $formattedDatetime = Carbon::parse($request->date)->format('Y-m-d H:i:s');
    $request->merge(['date' => $formattedDatetime, 'user_id' => $user->id]);

    return parent::createOne($request);
  }

    public function updateOne($id,Request $request)
  {
    
    $request->validate($this->rules);

    $formattedDatetime = Carbon::parse($request->date)->format('Y-m-d H:i:s');
    $request->merge(['date' => $formattedDatetime]);

    return parent::updateOne($id, $request);
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

    $item = Event::with('user:name,id')->find($id);

    if (!$item) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
        }

    return response()->json([
      'success' => true,
      'data' => ['item' => $item],
    ]);
  }

    public function cancelOne(Request $request, $id)
    {   
        if (in_array('cancel', $this->restricted)) {
            $user = $request->user();
            if (!$user->hasPermission($this->table, 'cancel')) {
                return response()->json([
                'success' => false,
                'errors' => [__('common.permission_denied')]
                ]);
            }
        }
        $event = $this->modelClass::find($id);

        if (!$event) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
    }

        // Check if the event is already canceled
        if ($event->is_canceled) {
            return response()->json(['message' => 'Event is already canceled'], 400);
        }

        // Cancel the event
        $event->is_canceled = true;
        $event->save();

        return response()->json(['message' => 'Event canceled successfully']);
    }

    public function restoreOne(Request $request, $id)
    {
        if (in_array('cancel', $this->restricted)) {
            $user = $request->user();
            if (!$user->hasPermission($this->table, 'cancel')) {
                return response()->json([
                'success' => false,
                'errors' => [__('common.permission_denied')]
                ]);
            }
        }
        $event = $this->modelClass::find($id);

        if (!$event) {
      return response()->json([
        'success' => false,
        'errors' => [__(Str::of($this->table)->replace('_', '-') . '.not_found')]
      ]);
    }

        // Check if the event is already restored
        if (!$event->is_canceled) {
            return response()->json(['message' => 'Event is not canceled'], 400);
        }

        // Restore the event
        $event->is_canceled = false;
        $event->save();

        return response()->json(['message' => 'Event restored successfully']);
    }

    public function readOwn(Request $request, $id)
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
    $items = $this->model()->where('user_id', $id)->get();

    // If user has permission to read own items only, then filter the items
    if (!$user->hasPermission($this->table, 'read')) {
      $items = $items->filter(function ($model) use ($user) {
        return $user->hasPermission($this->table, 'read', $model->id);
      })->values();
    }

    return response()->json([
      'success' => true,
      'data' => ['items' => $items],
    ]);
  }

    public function readRegistered(Request $request, $id)
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
    $items = User::find($id)->usersEvents;

    // If user has permission to read own items only, then filter the items
    if (!$user->hasPermission($this->table, 'read')) {
      $items = $items->filter(function ($model) use ($user) {
        return $user->hasPermission($this->table, 'read', $model->id);
      })->values();
    }

    return response()->json([
      'success' => true,
      'data' => ['items' => $items],
    ]);
  }
}
