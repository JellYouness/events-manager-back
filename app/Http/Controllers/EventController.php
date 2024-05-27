<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
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
    ];

    public function createOne(Request $request)
  {
    $request->validate($this->rules);
    
    $formattedDatetime = Carbon::parse($request->date)->format('Y-m-d H:i:s');
    $request->merge(['date' => $formattedDatetime]);

    return parent::createOne($request);
  }

    public function updateOne($id,Request $request)
  {
    $request->validate($this->rules);

    $formattedDatetime = Carbon::parse($request->date)->format('Y-m-d H:i:s');
    $request->merge(['date' => $formattedDatetime]);

    return parent::updateOne($id, $request);
  }

    public function cancelOne(Request $request, $id)
    {
        $event = $this->modelClass::findOrFail($id);

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
        $event = $this->modelClass::findOrFail($id);

        // Check if the event is already restored
        if (!$event->is_canceled) {
            return response()->json(['message' => 'Event is not canceled'], 400);
        }

        // Restore the event
        $event->is_canceled = false;
        $event->save();

        return response()->json(['message' => 'Event restored successfully']);
    }
}
