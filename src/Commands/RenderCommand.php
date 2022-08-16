<?php

namespace Surgiie\BladeCLI\Commands;

use Throwable;
use Dotenv\Dotenv;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Surgiie\BladeCLI\Blade;
use Symfony\Component\Finder\Finder;
use Surgiie\BladeCLI\Support\Command;
use Surgiie\BladeCLI\Support\OptionsParser;
use Symfony\Component\Console\Input\InputInterface;
use Surgiie\BladeCLI\Support\Concerns\LoadsJsonFiles;
use Symfony\Component\Console\Output\OutputInterface;
use Surgiie\BladeCLI\Support\Concerns\NormalizesPaths;
use Surgiie\BladeCLI\Support\Exceptions\FileNotFoundException;

class RenderCommand extends Command
{
    use LoadsJsonFiles, NormalizesPaths;

    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = "render {file}
                                   {--save-as= : The custom directory or file name to save the .rendered files to. }
                                   {--from-json=* : A json file to load variable data from. }
                                   {--from-env=* : A .env file to load variable data from. }
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
        "help",
        "quiet",
        "verbose",
        "version",
        "ansi",
        "save-as",
        "from-json",
        "from-env",
        "force",
        "no-interaction",
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
     * Set the options to use over parsed argv.
     *
     * This is mostly useful for tests.
     *
     * @param array $options
     * @return void
     */
    public static function useOptions(array $options = []): void
    {
        $parser = new OptionsParser($options);

        $options = $parser->parse(OptionsParser::VALUE_MODE);

        static::$staticOptions = $options;
    }

    /**
     * Parse arguments for options to register with the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param array $arguments
     * @return void
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

        $this->error($e->getMessage());

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
            $json = array_merge($json, $this->loadJsonFile($file));
            $this->comment("Gathered variable data from json file: $file");
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
            $env = array_merge($env, Dotenv::parse(file_get_contents($file)));
            $this->comment("Gathered variable data from env file: $file");
        }

        foreach ($env as $k => $v) {
            $env[$k] = $v;
        }

        return $env;
    }
    /**
     * Normalize key naming convention for the given data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeVariableNames(array $vars)
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

        $this->comment("Gathered variable data from command line options.");

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
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $options = $this->commandOptions;
        $force = $options['force'] ?? false;
        $file = rtrim($this->normalizePath($this->argument("file")), "\\/");

        if (!file_exists($file)) {
            return $this->handleException(new FileNotFoundException("The file or directory $file does not exist."));
        }

        $this->comment("Validated file path: $file");

        $variables = $this->gatherVariables();

        // process single file.
        if (is_file($file)) {
            $this->renderFile($file, $variables, $options);
        } else if (is_dir($file) && ($force || $this->confirm("Are you sure you want to render ALL files in the $file directory?"))) {
            $this->renderDirectoryFiles($file, $variables, $options);
        }

        return 0;
    }

    /**
     * Render a directory of files.
     *
     * @param string $directory
     * @param array $data
     * @param array $options
     * @return
     */
    protected function renderDirectoryFiles(string $directory, array $data, array $options)
    {
        // if($options['filename'] ?? false){
        //     throw new InvalidArgumentException("The filename option is only used when rendering a single file at a time.");
        // }

        // $finder = $this->finder();

        // $files = $finder->in($directory)->files();

        // $saveDirectory = rtrim($options['save-directory'] ?? "", "\\/");

        // // validate save directory isnt the current directory being processed.
        // if ($saveDirectory == $directory) {
        //     return $this->handleException(new InvalidArgumentException('The save directory is the directory you are rendering, select different directory.'));
        // }

        // foreach ($files as $file) {
        //     $pathName = $file->getPathName();

        //     if (! $saveDirectory) {
        //         $this->renderFile($pathName, $data, $options);

        //         continue;
        //     }

        //     // compute a save directory that mirrors the current location directory structure
        //     $computedDirectory = rtrim($saveDirectory, "\\/");

        //     $relativePath = ltrim(Str::after($pathName, $directory), "\\/");

        //     $options['save-directory'] = dirname(
        //         $computedDirectory . DIRECTORY_SEPARATOR . $relativePath
        //     );

        //     $this->renderFile($pathName, $data, $options);
        // }

        return $this;
    }

    /**
     * Get a new finder instance.
     *
     * @return \Symfony\Component\Finder\Finder
     */
    public function finder()
    {
        return new Finder();
    }

    /**
     * Renders a template file from path using given data.
     */
    protected function renderFile(string $file, array $data, array $options): string
    {
        $blade = $this->blade($file, $options);

        try {
            $result = $blade->render(data: $data);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }

        $file = $blade->getSaveLocation();

        $this->info("Rendered $file.");

        return $result;
    }

    /**
     * Register a dynamic option parsed from args.
     *
     * @param string $name
     * @param int $mode
     * @return bool
     */
    public function registerOption($name, $mode): bool
    {
        if ($this->isReservedOption($name)) {
            return false;
        }

        $this->addOption($name, mode: $mode);

        return true;
    }

    /**
     * Check if the given option name is a reserved one.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedOption($name)
    {
        return in_array($name, $this->reservedOptions);
    }
}
