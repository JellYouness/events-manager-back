<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CrudController;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends CrudController
{
  protected $table = 'uploads';
  protected $modelClass = Upload::class;
  protected $rules = [
    'name' => 'nullable|string',
    'file' => 'required|file',
  ];

  public function createOne(Request $request)
  {
    $request->validate($this->rules);
    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();
    $filename = time() . '.' . $extension;
    Storage::disk('cloud')->put($filename, $file->get());
    $path = "/cloud/$filename";
    $request->merge(['path' => $path]);
    return parent::createOne($request);
  }

  public function updateOne($id, Request $request)
  {
    $request->validate($this->rules);

    $currentPath = $this->model()->find($id)->path;
    if ($currentPath) {
      $currentPath = str_replace('/cloud', '', $currentPath);
      Storage::disk('cloud')->delete($currentPath);
    }

    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();
    $filename = time() . '.' . $extension;
    Storage::disk('cloud')->put($filename, $file->get());
    $path = "/cloud/$filename";
    $request->merge(['path' => $path]);
    return parent::updateOne($id, $request);
  }

  public function afterDeleteOne($item, $request)
  {
    $path = $item->path;
    if ($path) {
      $path = str_replace('/cloud', '', $path);
      Storage::disk('cloud')->delete($path);
    }
  }

  public function readImage($id)
  {
    $upload = $this->model()->find($id);
    if (!$upload) {
      return response()->json(['error' => 'Upload not found'], 404);
    }
    $path = str_replace('/cloud', '', $upload->path);
    if (!Storage::disk('cloud')->exists($path)) {
      return response()->json(['error' => 'File not found'], 404);
    }
    return Storage::disk('cloud')->response($path);
  }
}
