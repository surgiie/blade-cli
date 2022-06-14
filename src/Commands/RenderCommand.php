<?php

namespace BladeCLI\Commands;

use BladeCLI\Blade;
use BladeCLI\Support\Command;
use BladeCLI\Support\Concerns\LoadsJsonFiles;
use BladeCLI\Support\Exceptions\FileNotFoundException;
use BladeCLI\Support\OptionsParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

class RenderCommand extends Command
{
    use LoadsJsonFiles;

    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = "render {file}
                                   {--save-directory= : The custom directory to save the .rendered files to. }
                                   {--from-json=* : A file to load variable data from. }
                                   {--force : Force render or overwrite files.}";


    /**
     * An array of options use statically if not using command from command line.
     *
     * @var array
     */
    protected static ?array $staticOptions = null;

    /**
     * Set the options for the command.
     *
     * @var array
     */
    protected array $commandOptions = [];

    /**
     * The options that are reserved for the command
     * and cannot be used as variable data options.
     *
     * @var array
     */
    protected array $reservedOptions = [
        "help",
        "quiet",
        "verbose",
        "version",
        "ansi",
        "save-directory",
        "from-json",
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
     *
     * @return void
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
    public static function useOptions(array $options = [])
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
    protected function parseArgsForCommandOptions(InputInterface $input, array $arguments = [])
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
     * @param Throwable $e
     * @return int
     */
    public function handleException(Throwable $e)
    {
        if (Blade::isFaked()) {
            throw $e;
        }

        $this->error($e->getMessage());

        return 1;
    }
    /**
     * Initialize command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!is_null(static::$staticOptions)) {
            $this->commandOptions = static::$staticOptions;
        } else {
            global $argv;

            $this->parseArgsForCommandOptions($input, $argv);
        }
    }

    /**
     * Normalize key naming convention for the given data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeRenderData(array $data)
    {
        $result = [];

        foreach ($data as $k => $v) {
            $result[Str::camel($k)] = $v;
        }

        return $result;
    }

    /**
     * Gather the data to be used to render.
     *
     * @return array
     */
    protected function gatherRenderData()
    {
        $data = $this->normalizeRenderData($this->gatherFileVariableData());

        return array_merge($data, $this->normalizeRenderData($this->gatherOptionVariableData()));
    }

    /**
     * Gather the data for rendering from command line options.
     *
     * @return array
     */
    protected function gatherOptionVariableData()
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
     * Normalize a path from linux to windows or vice versa.
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path)
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            return str_replace("/", "\\", $path);
        } else {
            return str_replace("\\", "/", $path);
        }
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle()
    {
        $options = $this->commandOptions;

        $file = $this->normalizePath($this->argument("file"));

        if (!file_exists($file)) {
            return $this->handleException(new FileNotFoundException("The file or directory $file does not exist."));
        }

        $data = $this->gatherRenderData();

        // process single file.
        if (is_file($file)) {
            $this->renderFile($file, $data, $options);

            return 0;
        }

        $file = rtrim($file, "\\/");


        if (is_dir($file) && ($options['force'] ?? false || $this->confirm("Are you sure you want to render ALL files in the $file directory?"))) {

            $this->renderDirectoryFiles($file, $data, $options);

            return 0;
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
        $finder = $this->finder();

        $files = $finder->in($directory)->files();

        $saveDirectory = rtrim($options['save-directory'] ?? "", "\\/");

        // validate save directory isnt the current directory being processed.
        if ($saveDirectory == $directory) {
            return $this->handleException(new InvalidArgumentException('The save directory is the directory you are rendering, select different directory.'));
        }

        foreach ($files as $file) {
            $pathName = $file->getPathName();

            if (!$saveDirectory) {
                $this->renderFile($pathName, $data, $options);

                continue;
            }

            // compute a save directory that mirrors the current location directory structure
            $computedDirectory = rtrim($saveDirectory, "\\/");

            $relativePath = ltrim(Str::after($pathName, $directory), "\\/");

            $options['save-directory'] = dirname(
                $computedDirectory . DIRECTORY_SEPARATOR . $relativePath
            );

            $this->renderFile($pathName, $data, $options);
        }

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
     *
     * @param string $file
     * @param array $data
     * @return int
     */
    protected function renderFile(string $file, array $data, array $options)
    {
        $blade = $this->blade($file, $options);

        try {
            $result = $blade->render(data: $data);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }

        if ($result !== false) {
            $file = $blade->getSaveLocation();

            $this->info("Rendered $file.");
        }

        return $result == false ? 1 : 0;
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

    /**
     * Get the data from json files.
     *
     * @param array $options
     * @return array
     */
    public function gatherJsonFileData(): array
    {
        $json = [];
        $jsonFiles = Arr::wrap($this->commandOptions["from-json"] ?? []);

        foreach ($jsonFiles as $file) {
            $json = array_merge($json, $this->loadJsonFile($file));
        }

        return $json;
    }

    /**
     * Get variable data from files.
     *
     * @return array
     */
    protected function gatherFileVariableData(): array
    {
        $vars = [];

        foreach ($this->gatherJsonFileData() as $k => $v) {
            if ($this->isReservedOption($k)) {
                continue;
            }
            $vars[$k] = $v;
        }

        return $vars;
    }
}
