<?php

namespace Surgiie\BladeCLI\Commands;

use BadMethodCallException;
use Dotenv\Dotenv;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Surgiie\BladeCLI\Blade;
use Surgiie\BladeCLI\Support\Command;
use Surgiie\BladeCLI\Support\Concerns\LoadsJsonFiles;
use Surgiie\BladeCLI\Support\Concerns\NormalizesPaths;
use Surgiie\BladeCLI\Support\Exceptions\FileNotFoundException;
use Surgiie\BladeCLI\Support\OptionsParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

class RenderCommand extends Command
{
    use LoadsJsonFiles;
    use NormalizesPaths;

    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = "render {file-or-directory}
                                   {--save-as= : The custom file path to save the rendered file to. }
                                   {--save-dir= : The custom directory to save files to when rendering an entire directory. }
                                   {--from-json=* : A json file to load variable data from. }
                                   {--from-env=* : A .env file to load variable data from. }
                                   {--dry-run : Show rendered file changes only. }
                                   {--force : Force render or overwrite files.}";


    /**
     * An array of options use statically if not using command from command line.
     */
    protected static ?array $staticOptions = null;

    /**
     * Set the options for the command.
     */
    protected array $commandOptions = [];

    /**
     * The options that are reserved for the command
     * and cannot be used as variable data options.
     */
    protected array $reservedOptions = [
        'help',
        'quiet',
        'verbose',
        'version',
        'ansi',
        'save-as',
        'save-dir',
        'dry-run',
        'from-json',
        'from-env',
        'force',
        'no-interaction',
    ];

    /**
     * The command's description.
     *
     * @var string
     */
    protected $description = "Render a template file.";

    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        parent::__construct();
        // ignore validation errors for dynamic options to work.
        $this->ignoreValidationErrors();
    }

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $options = $this->commandOptions;

        $path = $originalPath = rtrim($this->normalizePath($this->argument("file-or-directory")), "\\/");

        if (Blade::isFaked()) {
            $path = Blade::testPath($path);
        }

        if (!file_exists($path)) {
            return $this->handleException(new FileNotFoundException("The target file or directory '$path' does not exist."));
        }

        $variables = $this->gatherVariables();

        if (is_file($path)) {
            $dryRun = $options['dry-run'] ?? false;
            return $dryRun ? $this->showRenderedContents($originalPath, $variables, $options) : $this->renderFile($originalPath, $variables, $options);
        }

        return $this->renderDirectoryFiles($originalPath, $variables, $options);
    }

    /**
     * Render a directory of files.
     */
    protected function renderDirectoryFiles(string $directory, array $data, array $options): int
    {
        if (empty($options['save-dir'] ?? "")) {
            return $this->handleException(new BadMethodCallException("The --save-dir option is required when rendering an entire directory."));
        }

        if ($faked = Blade::isFaked()) {
            $directory = Blade::testPath($directory);
        }

        $force = $options['force'] ?? false;

        if (!$force && !$this->confirm("Are you sure you want to render ALL files in the $directory directory?")) {
            return 1;
        }

        $saveDirectory = rtrim($options['save-dir'], "\\/");

        // validate save directory isnt the current directory being processed.
        if ($saveDirectory == $directory) {
            return $this->handleException(new InvalidArgumentException('The --save-dir is the directory you are rendering, select different directory.'));
        }

        foreach ((new Finder())->in($directory)->files() as $file) {
            $pathName = $file->getPathName();
            // compute a save as location that mirrors the current location of this file.
            $computedDirectory = rtrim($saveDirectory, "\\/");

            $relativePath = ltrim(Str::after($pathName, $directory), "\\/");

            $options['save-as'] = dirname(
                $computedDirectory . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $file->getFileName()
            );

            $this->renderFile($faked ? ltrim(str_replace(Blade::testPath(), "", $pathName), "\\/") : $pathName, $data, $options);
        }

        return 1;
    }


    /**
     * Show the rendered contents of a file only.
     */
    protected function showRenderedContents(string $file, array $data, array $options): int
    {
        $blade = $this->blade($file, $options);
        try {
            $contents = $blade->render(data: $data, save: false);
            $this->components->info("This command would generate the following content for $file:");
            $this->line("");
            $this->line($contents);
            $this->line("");
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
        return 0;
    }
    /**
     * Renders a template file from path using given data.
     */
    protected function renderFile(string $file, array $data, array $options): int
    {
        $blade = $this->blade($file, $options);

        try {
            $blade->render(data: $data);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }

        $file = $blade->getSaveLocation();

        $this->components->info("Rendered $file.");

        return 0;
    }

    /**
     * Set the options to use over parsed argv.
     *
     * This is mostly useful for tests.
     */
    public static function useOptions(array $options = []): void
    {
        $parser = new OptionsParser($options);

        $options = $parser->parse(OptionsParser::VALUE_MODE);
        static::$staticOptions = $options;
    }

    /**
     * Parse arguments for options to register with the command.
     */
    protected function parseArgsForCommandOptions(InputInterface $input, array $arguments = []): void
    {
        $parser = new OptionsParser(array_slice($arguments, 3));

        foreach ($parser->parse() as $name => $mode) {
            $this->registerOption($name, $mode);
        }

        //rebind input definition
        $input->bind($this->getDefinition());

        $this->commandOptions = $this->options();
    }

    /**
     * Handle a raised exception during the command call.
     *
     * @throws \Throwable
     */
    public function handleException(Throwable $e): int
    {
        if (Blade::isFaked()) {
            throw $e;
        }

        $this->components->error($e->getMessage());

        return 1;
    }

    /**
     * Initialize command.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (!is_null(static::$staticOptions)) {
            $this->commandOptions = static::$staticOptions;
        } else {
            global $argv;

            $this->parseArgsForCommandOptions($input, $argv);
        }
    }

    /**
     * Get the variables from json files.
     */
    protected function gatherJsonFileVariables(): array
    {
        $json = [];
        $jsonFiles = Arr::wrap($this->commandOptions["from-json"] ?? []);

        foreach ($jsonFiles as $file) {
            if (Blade::isFaked()) {
                $file = Blade::testPath($file);
            }
            $json = array_merge($json, $this->loadJsonFile($file));
        }

        return $json;
    }

    /**
     * Get the variables from env files.
     */
    protected function gatherEnvFileVariables(): array
    {
        $env = [];
        $envFiles = Arr::wrap($this->commandOptions["from-env"] ?? []);

        foreach ($envFiles as $file) {
            if (Blade::isFaked()) {
                $file = Blade::testPath($file);
            }
            $env = array_merge($env, Dotenv::parse(file_get_contents($file)));
        }

        foreach ($env as $k => $v) {
            $env[$k] = $v;
        }

        return $env;
    }

    /**
     * Normalize key naming convention for the given data.
     */
    protected function normalizeVariableNames(array $vars): array
    {
        $result = [];

        foreach ($vars as $k => $v) {
            $result[Str::camel(strtolower($k))] = $v;
        }

        return $result;
    }

    /**
     * Gather the data for rendering from command line options.
     */
    protected function gatherCommandLineVariables(): array
    {
        $vars = [];

        foreach ($this->commandOptions as $k => $v) {
            if ($this->isReservedOption($k)) {
                continue;
            }

            $vars[$k] = $v;
        }

        return $vars;
    }

    /**
     * Gather the data to be used to render.
     */
    protected function gatherVariables(): array
    {
        $variables = array_merge(
            $this->normalizeVariableNames($this->gatherJsonFileVariables()),
            $this->normalizeVariableNames($this->gatherEnvFileVariables()),
            $this->normalizeVariableNames($this->gatherCommandLineVariables())
        );
        $result = [];

        foreach ($variables as $k => $v) {
            $result[Str::camel($k)] = $v;
        }

        return $result;
    }

    /**
     * Register a dynamic option parsed from args.
     */
    public function registerOption(string $name, int $mode): bool
    {
        if ($this->isReservedOption($name)) {
            return false;
        }

        $this->addOption($name, mode: $mode);

        return true;
    }

    /**
     * Check if the given option name is a reserved one.
     */
    protected function isReservedOption(string $name): bool
    {
        return in_array($name, $this->reservedOptions);
    }
}
