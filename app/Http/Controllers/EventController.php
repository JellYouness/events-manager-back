<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends CrudController
{
    protected $table = 'events';
    protected $modelClass = Event::class;
}
