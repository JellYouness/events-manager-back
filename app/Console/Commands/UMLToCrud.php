<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UMLToCrud extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'uml:generate {--reset} {--refresh}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate migrations + models + controllers + routes from UML';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    try {
      $reset = $this->option('reset');
      if ($reset === true) {
        $this->reset();
        return Command::SUCCESS;
      }
      $refresh = $this->option('refresh');
      if ($refresh === true) {
        $this->reset();
      }
      $config = config('uml');
      $entities = $config['entities'];
      foreach ($entities as $index => $entity) {
        $this->info("Generating CRUD for {$entity['table']}...");
        $this->generateMigration($entity, $index);
        if (isset($entity['model'])) {
          $this->generateModel($entity, $entities);
          $this->generateController($entity);
          $this->generateEnums($entity);
          $this->generateLangs($entity);
        }
      }
      $this->generatePermissions($entities);
      $this->generateRoutes($entities);
      return Command::SUCCESS;
    } catch (\Exception $e) {
      $this->error($e->getMessage());
      // stack trace
      $this->error($e->getTraceAsString());
      return Command::FAILURE;
    }
  }
  public function generatePermissions($entities)
  {
    $disk = Storage::disk('stubs');
    $stub = $disk->get('permission-seeder.stub');

    $entities = array_filter($entities, function ($entity) {
      return isset($entity['model']);
    });
    $permissionsStr = '';
    foreach ($entities as $index => $entity) {
      if ($index !== array_key_first($entities)) {
        $permissionsStr .= $this->tab(2);
      }
      $permissionsStr .= "\$this->createScopePermissions('{$entity['table']}', ['create', 'read', 'update', 'delete']);";
      $permissionsStr .= PHP_EOL;
    }
    $permissionsToRolesStr = '';
    foreach ($entities as $index => $entity) {
      if ($index !== array_key_first($entities)) {
        $permissionsToRolesStr .= $this->tab(2);
      }
      $permissionsToRolesStr .= "\$this->assignScopePermissionsToRole(\$adminRole, '{$entity['table']}', ['create', 'read', 'update', 'delete']);";
      if ($index !== array_key_last($entities)) {
        $permissionsToRolesStr .= PHP_EOL;
      }
    }
    $data = [
      'permissions' => $permissionsStr,
      'permissionsToRoles' => $permissionsToRolesStr,
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('seeders');
    $filename = 'CrudPermissionSeeder.php';
    $disk->put($filename, $file);
    $this->saveGeneration('seeders', $filename);
  }
  public function generateLangs($entity)
  {
    $filenameEN = 'en/' . Str::of($entity['table'])->replace('_', '-') . '.php';
    $filenameFR = 'fr/' . Str::of($entity['table'])->replace('_', '-') . '.php';
    $disk = Storage::disk('langs');

    if ($disk->exists($filenameEN)) {
      $this->error("Lang EN {$filenameEN} already exists");
      return;
    }
    if ($disk->exists($filenameFR)) {
      $this->error("Lang FR {$filenameFR} already exists");
      return;
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('lang.stub');
    $data = [];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('langs');
    $disk->put($filenameEN, $file);
    $this->saveGeneration('langs', $filenameEN);
    $disk->put($filenameFR, $file);
    $this->saveGeneration('langs', $filenameFR);
  }
  public function generateEnums($entity)
  {
    $fields = $entity['fields'];
    foreach ($fields as $field) {
      if ($field['type'] === 'enum') {
        $enum = $field['enum'];
        $name = $enum['name'];
        $filename = "{$name}.php";
        $disk = Storage::disk('enums');

        if ($disk->exists($filename)) {
          $this->error("Enum {$filename} already exists");
          return;
        }

        $valuesStr = '';
        foreach ($enum['values'] as $key => $value) {
          if ($key !== array_key_first($enum['values'])) {
            $valuesStr .= $this->tab(1);
          }
          if (is_string($key)) {
            $valuesStr .= "case {$key} = '{$value}';";
          } else {
            $valuesStr .= "case {$value} = '{$value}';";
          }
          if ($key !== array_key_last($enum['values'])) {
            $valuesStr .= PHP_EOL;
          }
        }
        $disk = Storage::disk('stubs');
        $stub = $disk->get('enum.stub');
        $data = [
          'name' => $name,
          'values' => $valuesStr,
        ];
        $file = $this->replaceStub($stub, $data);

        $disk = Storage::disk('enums');
        $disk->put($filename, $file);
        $this->saveGeneration('enums', $filename);
      }
    }
  }
  public function generateRoutes($entities)
  {
    $disk = Storage::disk('stubs');
    $stub = $disk->get('crud-api.stub');
    $importsStr = '';

    $entities = array_filter($entities, function ($entity) {
      return isset($entity['model']);
    });
    foreach ($entities as $index => $entity) {
      $importsStr .= "use App\Http\Controllers\\{$entity['model']}Controller;";
      if ($index !== array_key_last($entities)) {
        $importsStr .= PHP_EOL;
      }
    }
    $routesStr = '';
    foreach ($entities as $index => $entity) {
      if (!isset($entity['model'])) {
        continue;
      }
      if ($index !== array_key_first($entities)) {
        $routesStr .= $this->tab(1);
      }
      $prefix = Str::of($entity['table'])->replace('_', '-');
      $routesStr .= "Route::prefix('{$prefix}')->name('{$entity['table']}.')->group(function () {" . PHP_EOL;
      $routesStr .= "{$this->tab(2)}Route::controller({$entity['model']}Controller::class)->group(function () {" . PHP_EOL;
      $routesStr .= "{$this->tab(3)}Route::post('/', 'createOne');" . PHP_EOL;
      $routesStr .= "{$this->tab(3)}Route::get('/{id}', 'readOne');" . PHP_EOL;
      $routesStr .= "{$this->tab(3)}Route::get('/', 'readAll');" . PHP_EOL;
      $routesStr .= "{$this->tab(3)}Route::put('/{id}', 'updateOne');" . PHP_EOL;
      $routesStr .= "{$this->tab(3)}Route::delete('/{id}', 'deleteOne');" . PHP_EOL;
      $routesStr .= "{$this->tab(2)}});" . PHP_EOL;
      $routesStr .= "{$this->tab(1)}});";
      if ($index !== array_key_last($entities)) {
        $routesStr .= PHP_EOL;
      }
    }
    $data = [
      'imports' => $importsStr,
      'routes' => $routesStr,
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('routes');
    $filename = 'crud-api.php';
    $disk->put($filename, $file);
    $this->saveGeneration('routes', $filename);
  }
  public function generateController($entity)
  {
    $filename = "{$entity['model']}Controller.php";
    $disk = Storage::disk('controllers');

    if ($disk->exists($filename)) {
      $this->error("Controller {$filename} already exists");
      return;
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('controller.stub');
    $data = [
      'table' => $entity['table'],
      'model' => $entity['model'],
      'models' => Str::plural($entity['model']),
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('controllers');
    $disk->put($filename, $file);
    $this->saveGeneration('controllers', $filename);
  }
  public function generateModel($entity, $entities)
  {
    $filename = "{$entity['model']}.php";
    $disk = Storage::disk('models');

    if ($disk->exists($filename)) {
      $this->error("Model {$filename} already exists");
      return;
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('model.stub');
    $data = [
      'imports' => $this->generateImports($entity['fields']),
      'model' => $entity['model'],
      'fillable' => $this->generateModelFillable($entity['fields']),
      'casts' => $this->generateModelCasts($entity['fields']),
      'relations' => $this->generateModelRelations($entity, $entities),
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('models');
    $disk->put($filename, $file);
    $this->saveGeneration('models', $filename);
  }
  public function generateModelCasts($fields)
  {
    $str = '';
    // enm or boolean
    $castable_fields = array_filter($fields, function ($field) {
      return $field['type'] === 'boolean' || $field['type'] === 'enum' || $field['type'] === 'timestamp';
    });
    foreach ($castable_fields as $name => $field) {
      if ($name !== array_key_first($castable_fields)) {
        $str .= $this->tab(2);
      }
      if ($field['type'] === 'enum') {
        $str .= "'{$name}' => {$field['enum']['name']}::class,";
      } elseif ($field['type'] === 'boolean') {
        $str .= "'{$name}' => 'boolean',";
      } elseif ($field['type'] === 'timestamp') {
        $str .= "'{$name}' => 'datetime',";
      }
      if ($name !== array_key_last($castable_fields)) {
        $str .= PHP_EOL;
      }
    }
    return $str === '' ? null : $str;
  }
  public function generateModelFillable($fields)
  {
    $fillableStr = '';
    foreach ($fields as $name => $field) {
      if ($name !== array_key_first($fields)) {
        $fillableStr .= $this->tab(2);
      }
      $fillableStr .= "'{$name}',";
      if ($name !== array_key_last($fields)) {
        $fillableStr .= PHP_EOL;
      }
    }
    return $fillableStr;
  }
  public function generateModelRelations($entity, $entities)
  {
    $fields = $entity['fields'];
    $relationsStr = '';

    // HasMany N,N Relations
    // Should check in both entities/directions A_B and B_A
    foreach ($entities as $_entity) {
      // Check that contains a field with the foreign table name
      if (!array_key_exists(Str::singular($entity['table']) . '_id', $_entity['fields'])) {
        continue;
      }
      if (Str::contains($_entity['table'], $entity['table'] . '_')) {
        if ($relationsStr !== '') {
          $relationsStr .= PHP_EOL;
        }
        $foreignTable = Str::replaceFirst($entity['table'] . '_', '', $_entity['table']);
        // Stud with lowercase first letter
        $foreignTable = Str::camel($foreignTable);
        $relationsStr .= $this->tab(1) . "public function {$foreignTable}() {" . PHP_EOL;
        $foreignModel = Str::singular(Str::studly($foreignTable));
        $relationsStr .= $this->tab(2) . "return \$this->hasMany('App\\Models\\{$foreignModel}');" . PHP_EOL;
        $relationsStr .= $this->tab(1) . '}';
      } elseif (Str::contains($_entity['table'], '_' .  $entity['table'])) {
        if ($relationsStr !== '') {
          $relationsStr .= PHP_EOL;
        }
        $foreignTable = Str::replaceFirst('_' . $entity['table'], '', $_entity['table']);
        $foreignTable = Str::camel($foreignTable);
        $relationsStr .= $this->tab(1) . "public function {$foreignTable}() {" . PHP_EOL;
        $foreignModel = Str::singular(Str::studly($foreignTable));
        $relationsStr .= $this->tab(2) . "return \$this->hasMany('App\\Models\\{$foreignModel}');" . PHP_EOL;
        $relationsStr .= $this->tab(1) . '}';
      }
    }
    // HasMany 1,N Relations
    foreach ($entities as $_entity) {
      // Check if entity has a foreign key to this entity
      $singularTable = Str::singular($entity['table']);
      if (isset($_entity['fields'][$singularTable . '_id']) && isset($_entity['model'])) {
        // Check is not unique
        $dependency = $_entity['fields'][$singularTable . '_id'];
        if (isset($dependency['unique']) && $dependency['unique'] === true) {
          continue;
        }
        if ($relationsStr !== '') {
          $relationsStr .= PHP_EOL;
        }
        $foreignTable = $_entity['table'];
        $foreignTable = Str::camel($foreignTable);
        $relationsStr .= $this->tab(1) . "public function {$foreignTable}() {" . PHP_EOL;
        $foreignModel = Str::singular(Str::studly($foreignTable));
        $relationsStr .= $this->tab(2) . "return \$this->hasMany('App\\Models\\{$foreignModel}');" . PHP_EOL;
        $relationsStr .= $this->tab(1) . '}';
      }
    }

    // BelongsTo Relations
    foreach ($fields as $name => $field) {
      if ($field['type'] === 'foreign') {
        if ($relationsStr !== '') {
          $relationsStr .= PHP_EOL;
        }
        $foreignTable = Str::replaceLast('_id', '', $name);
        $foreignModel = Str::singular(Str::studly($foreignTable));
        $foreignTable = Str::replaceFirst($entity['table'] . '_', '', $foreignTable);
        $foreignTable = Str::camel($foreignTable);
        $relationsStr .= $this->tab(1) . "public function {$foreignTable}() {" . PHP_EOL;
        $relationsStr .= $this->tab(2) . "return \$this->belongsTo('App\\Models\\{$foreignModel}');" . PHP_EOL;
        $relationsStr .= $this->tab(1) . '}';
      }
    }

    // HasOne Relations
    /*
      - Filter out all entities that have a unique foreign key to this entity
      - If found, add a hasOne relation to this entity
    */
    foreach ($entities as $_entity) {
      // Check if entity has a foreign key to this entity
      $singularTable = Str::singular($entity['table']);
      if (isset($_entity['fields'][$singularTable . '_id']) && isset($_entity['model'])) {
        $dependency = $_entity['fields'][$singularTable . '_id'];
        if (isset($dependency['unique']) && $dependency['unique'] === true) {
          if ($relationsStr !== '') {
            $relationsStr .= PHP_EOL;
          }
          $foreignTable = Str::singular($_entity['table']);
          $foreignTable = Str::camel($foreignTable);
          $relationsStr .= $this->tab(1) . "public function {$foreignTable}() {" . PHP_EOL;
          $foreignModel = Str::singular(Str::studly($foreignTable));
          $relationsStr .= $this->tab(2) . "return \$this->hasOne('App\\Models\\{$foreignModel}');" . PHP_EOL;
          $relationsStr .= $this->tab(1) . '}';
        }
      }
    }

    // Remove first tab from string, by searching for first $this->tab(2) and removing it
    $relationsStr = substr($relationsStr, strlen($this->tab(1)));
    return $relationsStr === '' ? null : $relationsStr;
  }
  public function generateMigration($entity, $index)
  {
    $date = date('Y_m_d_His', time() + $index);
    $filenameBase = "create_{$entity['table']}_table";
    $filename = $date . "_{$filenameBase}.php";
    $files = Storage::disk('migrations')->files();
    foreach ($files as $file) {
      if (Str::contains($file, $filenameBase)) {
        $this->error("Migration {$filenameBase} already exists");
        return;
      }
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('migration.stub');
    $imports = $this->generateImports($entity['fields']);
    $fields = $this->generateMigrationFields($entity['fields']);
    $unique = isset($entity['unique']) ? $this->generateMigrationUnique($entity['unique']) : null;
    $data = [
      'imports' => $imports,
      'table' => $entity['table'],
      'fields' => $fields,
      'unique' => $unique,
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('migrations');
    $disk->put($filename, $file);
    $this->saveGeneration('migrations', $filename);
  }
  public function generateImports($fields)
  {
    $importsStr = '';
    foreach ($fields as $field) {
      if ($field['type'] === 'enum') {
        $enum = $field['enum'];
        $name = $enum['name'];
        $importsStr .= "use App\Enums\\$name;";
      }
    }
    return $importsStr === '' ? null : $importsStr;
  }
  public function generateMigrationFields($fields)
  {
    $fieldsStr = '';
    foreach ($fields as $name => $field) {
      if ($name !== array_key_first($fields)) {
        $fieldsStr .= "{$this->tab(3)}";
      }
      if ($field['type'] === 'foreign') {
        $fieldsStr .= "\$table->foreignId('{$name}')";
        if (isset($field['foreign_table'])) {
          $foreignTable = $field['foreign_table'];
        } else {
          $foreignTable = str_replace('_id', '', $name);
          $foreignTable = Str::plural($foreignTable);
        }
        $fieldsStr .= "->constrained('{$foreignTable}')";
        $fieldsStr .= "->onDelete('cascade')";
      } elseif ($field['type'] === 'enum') {
        $enum = $field['enum'];
        $enumName = $enum['name'];
        $fieldsStr .= "\$table->enum('{$name}', array_column({$enumName}::cases(), 'value'))";
      } else {
        $fieldsStr .= "\$table->{$field['type']}('{$name}')";
      }
      if (isset($field['nullable']) && $field['nullable']) {
        $fieldsStr .= '->nullable()';
      }
      if (isset($field['unique']) && $field['unique']) {
        $fieldsStr .= '->unique()';
      }
      $fieldsStr .= ';';
      if ($name !== array_key_last($fields)) {
        $fieldsStr .= PHP_EOL;
      }
    }
    return $fieldsStr;
  }
  public function generateMigrationUnique($unique)
  {
    if (empty($unique)) {
      return null;
    }
    $uniqueStr = '';
    $uniqueStr .= "\$table->unique(['";
    $uniqueStr .= implode("', '", $unique);
    $uniqueStr .= "']);";
    return $uniqueStr;
  }
  public function generateCreateRequest($entity)
  {
    $model = $entity['model'];
    $filename = "{$model}/Create{$entity['model']}Request.php";
    $disk = Storage::disk('requests');

    if ($disk->exists($filename)) {
      $this->error("Controller {$filename} already exists");
      return;
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('create-request.stub');
    $data = [
      'model' => $model,
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('requests');
    $disk->put($filename, $file);
    $this->saveGeneration('requests', $filename);
  }
  public function generateUpdateRequest($entity)
  {
    $model = $entity['model'];
    $filename = "{$model}/Update{$entity['model']}Request.php";
    $disk = Storage::disk('requests');

    if ($disk->exists($filename)) {
      $this->error("Controller {$filename} already exists");
      return;
    }

    $disk = Storage::disk('stubs');
    $stub = $disk->get('update-request.stub');
    $data = [
      'model' => $model,
    ];
    $file = $this->replaceStub($stub, $data);

    $disk = Storage::disk('requests');
    $disk->put($filename, $file);
    $this->saveGeneration('requests', $filename);
  }
  public function replaceStub($stub, $data)
  {
    foreach ($data as $key => $value) {
      if ($value === null) {
        // Remove line that contains {{ $key }}
        $stub = preg_replace("/.*{{ $key }}.*\r\n/", '', $stub);
      } else {
        $stub = str_replace("{{ $key }}", $value, $stub);
      }
    }
    return $stub;
  }
  public function tab($count = 1)
  {
    return str_repeat(' ', $count * 2);
  }
  public function saveGeneration($disk, $filename)
  {
    $setting = Setting::where('key', 'crud-api.files')->first();
    if (!$setting) {
      $setting = new Setting();
      $setting->key = 'crud-api.files';
      $setting->value = json_encode([['disk' => $disk, 'filename' => $filename]]);
      $setting->save();
    } else {
      $generations = json_decode($setting->value, true);
      $exists = false;
      foreach ($generations as $generation) {
        if ($generation['disk'] === $disk && $generation['filename'] === $filename) {
          $exists = true;
          break;
        }
      }
      if (!$exists) {
        $generation = [
          'disk' => $disk,
          'filename' => $filename,
        ];
        $generations[] = $generation;
        $setting->value = json_encode($generations);
        $setting->save();
      }
    }
  }
  public function reset()
  {
    $this->info('Resetting...');
    $setting = Setting::where('key', 'crud-api.files')->first();
    if (!$setting) {
      $this->info('Resetting done');
      return;
    }
    $generations = json_decode($setting->value, true);
    foreach ($generations as $generation) {
      if ($generation['disk'] === 'routes') {
        $filename = 'crud-api.php';
        $disk = Storage::disk('stubs');
        $stub = $disk->get('crud-api.stub');
        $data = [
          'imports' => null,
          'routes' => null,
        ];
        $file = $this->replaceStub($stub, $data);

        $disk = Storage::disk('routes');
        $disk->put($filename, $file);
      } elseif ($generation['disk'] === 'seeders') {
        $filename = 'CrudPermissionSeeder.php';
        $disk = Storage::disk('stubs');
        $stub = $disk->get('permission-seeder.stub');
        $data = [
          'permissions' => null,
          'permissionsToRoles' => null,
        ];
        $file = $this->replaceStub($stub, $data);

        $disk = Storage::disk('seeders');
        $disk->put($filename, $file);
      } else {
        $disk = Storage::disk($generation['disk']);
        if ($disk->exists($generation['filename'])) {
          $disk->delete($generation['filename']);
        }
      }
    }
    $setting->value = json_encode([]);
    $setting->save();
    $this->info('Resetting done');
  }
}
