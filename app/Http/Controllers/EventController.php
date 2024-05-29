<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelNotificationMail;

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

  public function readAll(Request $request)
  {
    $user = $request->user();

    if (in_array('read_all', $this->restricted)) {
      if (!$user->hasPermission($this->table, 'read')) {
        return response()->json([
          'success' => false,
          'errors' => [__('common.permission_denied')]
        ]);
      }
    }

    $items = Event::withCount('usersEvents as participants')->get();

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
        $event = Event::with('usersEvents')->find($id);

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

        $users = DB::table('users_events')->where('event_id', $id)->join('users', 'users_events.user_id', '=', 'users.id')->select('users.*')->get();
        foreach ($users as $user) {
        Mail::to($user->email)->send(new CancelNotificationMail($user, $event));}

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

    $items = Event::withCount('usersEvents as participants')->where('user_id', $id)->get();

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

    $items = Event::withCount('usersEvents as participants')->where('user_id', $id)->get();

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
