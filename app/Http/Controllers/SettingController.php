<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class SettingController extends CrudController
{
    protected $table      = 'settings';
    protected $modelClass = Setting::class;
}
