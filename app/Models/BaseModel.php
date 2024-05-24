<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BaseModel extends Model
{
  // Abstract static property "cacheKey" must be defined in child class
  public static $cacheKey;

  protected static function booted()
  {
    parent::booted();
    $cacheKey = static::$cacheKey;

    static::created(function ($item) use ($cacheKey) {
      $items = Cache::get($cacheKey, collect([]));
      $items->push($item);
      Cache::put($cacheKey, $items);
    });

    static::updated(function ($item) use ($cacheKey) {
      $items = Cache::get($cacheKey, collect([]));
      $items = $items->map(function ($i) use ($item) {
        if ($i->id === $item->id) {
          return $item;
        }
        return $i;
      });
      Cache::put($cacheKey, $items);
    });

    static::deleted(function ($item) use ($cacheKey) {
      $items = Cache::get($cacheKey, collect([]));
      $items = $items->filter(function ($i) use ($item) {
        return $i->id !== $item->id;
      });
      Cache::put($cacheKey, $items);
    });
  }
}
