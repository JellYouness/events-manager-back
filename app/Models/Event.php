<?php

namespace App\Models;

class Event extends BaseModel
{
    public static $cacheKey = 'events';
    //protected $with = ['users'];
    protected $fillable = [
        'name',
        'date',
        'location',
        'description',
        'max_participants',
        'image',
        'is_canceled'
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
    public function usersEvents()
    {
        return $this->belongsToMany(User::class, 'users_events');
    }

    public function rules($id = null){
        $id = $id ?? request()->route('id');
        return [
            'name' => 'required|string',
            'date' => 'required',
            'location' => 'required|string',
            'description' => 'required|string',
            'max_participants' => 'required|integer',
            'image' => 'nullable|string',
            'is_canceled' => 'boolean',
        ];
    }

    /* public function participants()
    {
        return $this->belongsToMany(User::class, 'users_events')->wherePivot('status', 'accepted');
    } */
}
