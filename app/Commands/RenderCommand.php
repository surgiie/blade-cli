<?php

namespace App\Commands;

use App\Support\BaseCommand;
use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\LoadsJsonFiles;
use Surgiie\Console\Rules\FileOrDirectoryMustExist;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class RenderCommand extends BaseCommand
{
    use LoadsJsonFiles, LoadsEnvFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'render
                            {path : The file or directory path to compile and save file(s) for. }
                            {--save-to= : The custom file or directory path to save the rendered file(s) to. }
                            {--from-yaml=* : A yaml file path to load variable data from. }
                            {--from-json=* : A json file path to load variable data from. }
                            {--compiled-path= : Custom directory for the compiled/cached files. }
                            {--from-env=* : A .env file to load variable data from. }
                            {--confirm= : Add a confirmation prompt to this render call. }
                            {--dry-run : Dump out compiled file contents only. }
                            {--no-cache : Force recompile/dont use compiled cache file. }
                            {--force : Force render or overwrite files.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Render a file or directory of files.';

    /**Allow arbitrary options to be passed to the command. */
    protected bool $arbitraryOptions = true;

    /**
     * The validation rules for the input/options.
     */
    public function rules(): array
    {
        $path = $this->data->get('path');

        return [
            'path' => [new FileOrDirectoryMustExist("The file or directory :name '$path' does not exist")],
        ];
    }

    /**
     * The tranformers to run against arguments and options.
     */
    protected function transformers(): array
    {
        return [
            'path' => ['trim', 'normalize_path', 'rtrim::value:,'.DIRECTORY_SEPARATOR],
            'from-json' => [fn ($v) => Arr::wrap($v)],
            'from-yaml' => [fn ($v) => Arr::wrap($v)],
            'from-env' => [fn ($v) => Arr::wrap($v)],
            'save-to' => ['trim', 'normalize_path', 'rtrim::value:,'.DIRECTORY_SEPARATOR],
        ];
    }

    /**
     * Compute a save directory for the file being rendered.
     *
     * @return string
     */
    protected function computeSavePath(string $path, ?string $givenSavePath = null)
    {
        $separator = DIRECTORY_SEPARATOR;

        if ($givenSavePath && is_dir($givenSavePath)) {
            $saveToPath = rtrim($givenSavePath, $separator).$separator.$this->getDefaultSaveFileName($path);
        } elseif ($givenSavePath) {
            $saveToPath = $givenSavePath;
        } else {
            $saveToPath = $this->getDefaultSaveFilePath($path);
        }

        return $this->expandPath($saveToPath);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = $this->data->get('path');

        if ($this->data->get('confirm') && ! $this->components->confirm($this->data->get('confirm'))) {
            $this->exit('Aborted');
        }

        try {
            $variables = $this->gatherVariables();
        } catch (InvalidArgumentException $e) {
            $this->exit($e->getMessage());
        }

        if ($this->data->get('dry-run', false)) {
            $this->dryRun($path, $variables);

            return 0;
        }

        if (is_file($path)) {
            $this->renderFile($path, $variables, $this->computeSavePath($path, $this->data->get('save-to')));

            return 0;
        }

        if (! is_dir($path)) {
            return 1;
        }

        $saveFilePath = $this->expandPath($this->data->get('save-to'));

        if (! $saveFilePath) {
            $this->exit('The --save-to directory option is required when rendering all files in a directory.');
        }

        $this->renderDirectoryFiles($path, $variables, $saveFilePath);

        return 0;
    }

    /**
     * Render all files within a given directory.
     */
    protected function renderDirectoryFiles(string $path, array $variables, string $saveToPath)
    {
        // Ensure the path being processed isn't the same as the save directory
        if (rtrim($path, DIRECTORY_SEPARATOR) === rtrim($saveToPath, DIRECTORY_SEPARATOR)) {
            $this->exit('The path being processed is also the --save-to directory, use a different save directory.');
        }

        // Ensure the path being processed isn't the same as the save directory
        if ($path === $saveToPath) {
            $this->exit('The path being processed is also the save directory, use a different save directory.');
        }

        // Check if save directory already exists and confirm overwrite
        if (is_dir($saveToPath) && $path !== $saveToPath && ! $this->data->get('force', false)) {
            $this->exit("The save to directory '$saveToPath' already exists, use --force to overwrite.");
        }

        // Delete the save directory
        (new Filesystem)->deleteDirectory($saveToPath, preserve: true);

        // Confirm rendering all files in the path directory
        if (! $this->data->get('force', false) && ! $this->components->confirm("Are you sure you want to render ALL files in the '$path' directory?")) {
            $this->exit('Canceled');
        }

        // Ensure the save directory isn't the same as the directory being processed
        if ($saveToPath === $path) {
            return $this->exit('The --save-to is the directory you are rendering, select different save directory.');
        }

        // Render each file within the directory
        foreach ((new Finder())->in($path)->files() as $file) {
            $fileName = $file->getFileName();
            $pathName = $file->getPathName();

            // Compute a save as location that mirrors the current location of this file
            $computedDirectory = rtrim($saveToPath, DIRECTORY_SEPARATOR);
            $relativePath = ltrim(Str::after($pathName, $path), DIRECTORY_SEPARATOR);
            $saveDirectory = dirname(normalize_path("$computedDirectory/$relativePath/$fileName"));

            $this->renderFile($pathName, $variables, $saveDirectory);
        }
    }

    /**
     * Expand path if its a known path that can be expanded.
     */
    protected function expandPath(?string $path): string|null
    {
        $env = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? 'USERPROFILE' : 'HOME';

        return str_replace(['~/', '~/'], [getenv($env).'/', getenv($env).'/'], $path);
    }

    /**
     * Render a file and save its contents to the given path.
     */
    protected function renderFile(string $path, array $variables, string $saveTo): void
    {
        $saveDirectory = dirname($saveTo);

        if (! is_dir($saveDirectory)) {
            @mkdir($saveDirectory, recursive: true);
        }

        if (! is_writable($saveDirectory)) {
            $this->exit("The save directory $saveDirectory is not writable.");
        }

        if (file_exists($saveTo) && ! $this->data->get('force', false)) {
            $this->exit("The rendered file '$saveTo' already exists, use --force to overwrite.");
        }

        $task = $this->runTask("Render file $path to $saveTo", function ($task) use ($path, $variables, $saveTo) {
            try {
                $contents = $this->blade()->compile($path, $variables, cache: $this->data->get('no-cache') == false);

                return file_put_contents($saveTo, $contents) !== false;
            } catch (\Throwable $e) {
                $task->remember(['exception' => $e]);

                return false;
            }
        }, finishedText: "Rendered $saveTo");

        $data = $task->data();

        if (! $task->succeeded() && isset($data['exception'])) {
            $this->exit('Compile Error: '.$data['exception']->getMessage());
        }
    }

    /**
     * Get the default file name that will be used for the saved file.
     */
    protected function getDefaultSaveFileName(string $path): string
    {
        $info = new SplFileInfo($path);

        $basename = $info->getBasename('.'.$ext = $info->getExtension());

        if (strpos($basename, '.') === 0 && ".$ext" == $basename) {
            return $basename.'.rendered';
        } else {
            $basename .= '.rendered';
        }

        return $basename.($ext ? '.'.$ext : '');
    }

    /**
     * Get the default file path that will be used for the saved file.
     */
    protected function getDefaultSaveFilePath(string $path): string
    {
        $info = (new SplFileInfo($path));

        $saveDirectory = dirname($info->getRealPath()).DIRECTORY_SEPARATOR;

        return $saveDirectory.$this->getDefaultSaveFileName($path);
    }

    /**
     * Show the rendered contents for the given file.
     */
    protected function dryRun(string $path, array $variables = []): static
    {
        $dryRun = function ($filePath) use ($variables) {
            try {
                $contents = $this->blade()->compile($filePath, $variables, cache: $this->data->get('no-cache') == false);
                $this->message('DRY RUN', "Contents for $filePath:", bg: 'yellow', fg: 'black');
                foreach (explode(PHP_EOL, $contents) as $line) {
                    $this->line('  '.$line);
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->message('COMPILE ERROR', $e->getMessage(), bg: 'red', fg: 'white');
                $this->exit();
            }
        };

        $files = [];
        if (is_dir($path)) {
            foreach ((new Finder)->files()->in($path) as $file) {
                $files[] = $file->getRealPath();
            }
        } else {
            $files[] = $path;
        }
        foreach ($files as $file) {
            $dryRun($file);
            $this->newLine();
        }

        return $this;
    }

    /**
     * Get the variables from env files.
     */
    protected function gatherEnvFileVariables(): array
    {
        $env = [];
        $envFiles = $this->data->get('from-env', []);

        foreach ($envFiles as $file) {
            $env = array_merge($env, Dotenv::parse(file_get_contents($file)));
        }

        foreach ($env as $k => $v) {
            $env[$k] = $v;
        }

        return $env;
    }

    /**
     * Normalize variables to camel case for render.
     */
    protected function normalizeVariableNames(array $vars = []): array
    {
        $variables = [];
        foreach ($vars as $k => $value) {
            $variables[Str::camel(strtolower($k))] = $value;
        }

        return $variables;
    }

    /**
     * Gather the variables for rendering.
     */
    protected function gatherVariables(): array
    {
        $variables = [];
        // laod from yaml files.
        $yamlFiles = $this->data->get('from-yaml', []);

        foreach ($yamlFiles as $file) {
            $vars = Yaml::parseFile($file);
            $variables = array_merge($variables, $this->normalizeVariableNames($vars));
        }
        // laod from json files.
        $jsonFiles = $this->data->get('from-json', []);

        foreach ($jsonFiles as $file) {
            $vars = $this->loadJsonFile($file);
            $variables = array_merge($variables, $this->normalizeVariableNames($vars));
        }

        // load from env files.
        foreach ($this->data->get('from-env', []) as $file) {
            $vars = $this->getEnvFileVariables($file);
            $variables = array_merge($variables, $this->normalizeVariableNames($vars));
        }

        // lastly, command line options have highest precedence
        $variables = array_merge($variables, $this->normalizeVariableNames($this->arbitraryData->all()));

        return $variables;
    }
}
