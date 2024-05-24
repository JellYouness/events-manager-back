<?php

namespace App\Models;

class Upload extends BaseModel
{
  public static $cacheKey = 'uploads';
  protected $fillable = [
    'name',
    'path',
  ];
  public function rules($id = null)
  {
    $id = $id ?? request()->route('id');
    return [
      'name' => 'nullable|string',
      'path' => 'required|string',
    ];
  }
}
