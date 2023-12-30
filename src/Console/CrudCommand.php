<?php

namespace Musabbir035\Crud\Console;

use DirectoryIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate {model} {--fields=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate code for simple CRUD operations.';

    /**
     * The fields of the model.
     *
     * @var array
     */
    protected $modelFields;

    /**
     * Varoius formats of the model name.
     *
     * @var array
     */
    protected $modelNames;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelNameWithSpaces = trim(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', ' $0', $this->argument('model')));
        $this->modelFields = explode(',', $this->option('fields'));

        $this->modelNames = [
            'sample' => str_replace(' ', '', $modelNameWithSpaces),
            'with_space' => $modelNameWithSpaces,
            'with_spaces' => Str::plural($modelNameWithSpaces),
            'snake_case' => $snake = Str::snake($modelNameWithSpaces),
            'snake_cases' => $snakePlural = Str::plural($snake),
            'kebab_case' => $kebab = str_replace('_', '-', $snake),
            'kebab_cases' => Str::plural($kebab),
            'SampleMigration' => date('Y_m_d_His') . "_create_{$snakePlural}_table",
        ];

        $this->createFiles();
        $this->createViews();
        $this->appendRoute();
        $this->line("New routes appended to the routes/web.php file.");
        // Artisan::call('migrate');
        $this->line("Migration complete.");
    }

    private function createFiles()
    {
        $filePaths = [
            'sample' => app_path('Models'),
            'sampleController' => app_path('Http/Controllers'),
            'sampleRequest' => app_path('Http/Requests'),
            'SampleMigration' => database_path('migrations'),
        ];

        foreach ($filePaths as $key => $path) {
            $filePath = __DIR__ . "/../../resources/stubs/{$key}.stub";
            if (!file_exists($filePath)) continue;

            if (!file_exists($path)) {
                mkdir($path);
            }

            $stubFileContents = file_get_contents($filePath);
            $searches = array_keys($this->modelNames);
            $replaces = array_values($this->modelNames);
            $fileName = str_replace($searches, $replaces, $key);
            $newContents = str_replace($searches, $replaces, $stubFileContents);
            $file = "{$path}/{$fileName}.php";

            $newContents = $this->getFileContents($key, $newContents);
            file_put_contents($file, $newContents);
            $this->line("Created file: $file");
        }
    }

    private function getFileContents(string $key, string $content)
    {
        if ($key == 'sample') {
            return str_replace("'file_contents'", "'" . str_replace(",", "', '", $this->option('fields')) . "'", $content);
        }

        if ($key == 'sampleRequest') {
            $rules = '';
            foreach ($this->modelFields as $field) {
                $rules .= "'$field' => 'required',";
            }

            return str_replace("'file_contents'", $rules, $content);
        }

        if ($key == 'SampleMigration') {
            $columns = '';
            foreach ($this->modelFields as $field) {
                $columns .= '$table->string("' . $field . '");';
            }

            return str_replace("'file_contents'", $columns, $content);
        }

        return $content;
    }

    private function createViews()
    {
        $viewPath = resource_path('views/' . $this->modelNames['kebab_cases']);
        File::isDirectory($viewPath) or File::makeDirectory($viewPath, 0755, true);

        foreach (File::allFiles(__DIR__ . '/../../resources/stubs/views') as $stub) {
            $stubFileContents = file_get_contents($stub);
            $newContents = str_replace(array_keys($this->modelNames), array_values($this->modelNames), $stubFileContents);
            $stubName = $stub->getBasename();
            $file = $viewPath . '/' . str_replace('.stub', '.blade.php', $stubName);
            $newContents = $this->getViewContents($stubName, $newContents);
            file_put_contents($file, $newContents);
            $this->line("Created file: $file");
        }
    }

    private function getViewContents(string $stubName, string $contents)
    {
        if ($stubName == 'index.stub') {
            $headings = '';
            $rows = '';
            foreach ($this->modelFields as $field) {
                $headings .= '<th>' . ucfirst($field) . '</th>';
                $rows .= '<td>{{ $' . $this->modelNames['snake_case'] . '->' . $field . ' }}</td>';
            }

            $contents = str_replace('table_headings', $headings, $contents);

            return str_replace('table_rows', $rows, $contents);
        }

        if ($stubName == 'create.stub') {
            $fields = '';
            foreach ($this->modelFields as $field) {
                $fields .= '
                    <div class="col-12">
                        <label for="' . $field . '-id">' . ucfirst(preg_replace("/[\-_]/", " ", $field)) . '</label>
                        <input type="text" name="' . $field . '" id="' . $field . '-id" value="{{ old(\'' . $field . '\') }}" class="form-control" required>
                        @error(\'' . $field . '\')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                ';
            }

            return str_replace('page_contents', $fields, $contents);
        }

        if ($stubName == 'edit.stub') {
            $fields = '';
            foreach ($this->modelFields as $field) {
                $fields .= '
                    <div class="col-12">
                        <label for="' . $field . '-id">' . ucfirst(preg_replace("/[\-_]/", " ", $field)) . '</label>
                        <input type="text" name="' . $field . '" id="' . $field . '-id" class="form-control" value="{{ old(\'' . $field . '\', $' . $this->modelNames['snake_case'] . '-> ' . $field . ') }}" required>
                        @error(\'' . $field . '\')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                ';
            }

            return str_replace('page_contents', $fields, $contents);
        }

        if ($stubName == 'show.stub') {
            $fields = '';
            foreach ($this->modelFields as $field) {
                $fields .= '
                    <div class="col-12 py-2 border-bottom">
                        <div><strong>' . preg_replace("/[\-_]/", " ", $field) . '</strong></div>
                        <div>{{ $' . $this->modelNames['snake_case'] . '-> ' . $field . ' }}</div>
                    </div>
                ';
            }

            return str_replace('page_contents', $fields, $contents);
        }
    }

    private function appendRoute()
    {
        $route = "Route::resource('" . $this->modelNames['kebab_cases'] . "', \App\Http\Controllers\\" . $this->modelNames['sample'] . "Controller::class);";
        file_put_contents(base_path('routes/web.php'), $route . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
