<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;

class UserEventController extends Controller
{
    public function register(Request $request, $eventId)
    {
        $userId = $request->user_id;
        $user = User::findOrFail($userId);
        $user->usersEvents()->attach($eventId);
        
        return response()->json(['success' => true, 'message' => 'User registered to event successfully.']);
    }

    public function unregister(Request $request, $eventId)
    {
        $userId = $request->user_id;
        $user = User::findOrFail($userId);
        $user->usersEvents()->detach($eventId);
        
        return response()->json(['success' => true, 'message' => 'User unregistered to event successfully.']);
    }
}
