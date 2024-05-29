<?php

namespace App\Models;

class Event extends BaseModel
{
    public static $cacheKey = 'events';

    protected $fillable = [
        'name',
        'date',
        'location',
        'description',
        'max_participants',
        'image',
        'is_canceled',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usersEvents()
    {
        return $this->belongsToMany(User::class, 'users_events');
    }

    protected static function booted()
    {
        parent::booted();
        static::created(function ($event) {
            $user = User::findOrFail($event->user_id);
            $user->givePermission('events.'.$event->id.'.update');
        });
        static::deleted(function ($event) {
            $permissions = Permission::where('name', 'like', 'events.'.$event->id.'.%')->get();
            DB::table('users_permissions')->whereIn('permission_id', $permissions->pluck('id'))->delete();
            Permission::destroy($permissions->pluck('id'));
        });
    }

    public function rules($id = null)
    {
        $id = $id ?? request()->route('id');

        return [
            'name' => 'required|string',
            'date' => 'required',
            'location' => 'required|string',
            'description' => 'required|string',
            'max_participants' => 'required|integer',
            'image' => 'nullable|string',
            'is_canceled' => 'boolean',
            'is_registred' => 'boolean',
            'user_id' => 'required|integer',
        ];
    }

    /* public function participants()
    {
        return $this->belongsToMany(User::class, 'users_events')->wherePivot('status', 'accepted');
    } */
}
