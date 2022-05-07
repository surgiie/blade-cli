<?php

namespace BladeCLI\Commands;

use SplFileInfo;
use BladeCLI\Blade;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use BladeCLI\Support\Command;
use BladeCLI\Support\OptionsParser;
use Symfony\Component\Finder\Finder;
use BladeCLI\Support\Concerns\LoadsJsonFiles;
use BladeCLI\Support\Concerns\NormalizesPaths;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BladeCLI\Support\Exceptions\FileNotFoundException;

class RenderCommand extends Command
{
    use LoadsJsonFiles, NormalizesPaths;

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
     * and cannot be used as template data options.
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

            $arguments = $argv;

            $this->parseArgsForCommandOptions($input, $arguments);
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

        $file = $this->argument("file");

        if (!file_exists($file)) {
            throw new FileNotFoundException("The file or directory '$file' does not exist.");
        }

        $data = $this->getFileVariableData($options);
        // process single file.
        if (is_file($file)) {
            $this->renderFile($file, $data, $options);
        }
        // process an entire directory
        else if (is_dir($file) && $this->option('force') || $this->confirm("Are you sure you want to render files in the $file directory?")) {
            $this->renderDirectoryFiles($file, $data, $options);
        }

        return 0;
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
     * @return static
     */
    protected function renderFile(string $file, array $data, array $options): static
    {
        $blade = $this->blade($file, $options);

        $result = $blade->render(data: $data);

        if ($result !== false) {
            $file =  $blade->getSaveLocation();

            $this->info("Rendered $file");
        }

        return $this;
    }

    /**
     * Updates/computes the save directory for a file being
     * rendered during a directory render so that
     * it gets rendered in a mirrored location to its
     * current directory/location.
     *
     * @param array $options
     * @param string $filePath
     * @param array $options
     * @return array
     */
    protected function computeSaveDirectoryForDirectoryRender(string $directory, string $filePath, array $options)
    {
        $saveDirectory = $this->removeTrailingSlash($options['save-directory'] ?? "");

        if ($saveDirectory) {
            $relativePath = $this->removeLeadingSlash(Str::after($filePath, $directory));

            $options['save-directory'] =  dirname($this->normalizePath(
                $saveDirectory . DIRECTORY_SEPARATOR . $relativePath
            ));
        }

        return $options;
    }
    /**
     * Render a directory of files.
     *
     * @param string $directory
     * @param array $data
     * @param array $options
     * @return static
     */
    protected function renderDirectoryFiles(string $directory, array $data, array $options): static
    {
        $finder = $this->finder();

        $files = $finder->in($directory)->files();

        foreach ($files as $file) {
            $pathName = $file->getPathName();
            $renderOptions = $this->computeSaveDirectoryForDirectoryRender($directory, $pathName, $options);
            $this->renderFile($pathName, $data, $renderOptions);
        }

        return $this;
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
     * @return boolean
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
    public function getJsonFileData(array $options = []): array
    {
        $json = [];
        $jsonFiles = Arr::wrap($options["from-json"] ?? []);

        foreach ($jsonFiles as $file) {
            $json = array_merge($json, $this->loadJsonFile($file));
        }

        return array_merge($json, $options);
    }

    /**
     * Get the data to use for the render file.
     *
     * @param array $options
     * @return array
     */
    protected function getFileVariableData(array $options = []): array
    {
        $vars = [];

        foreach ($this->getJsonFileData(options: $options) as $k => $v) {
            if ($this->isReservedOption($k)) {
                continue;
            }
            $vars[Str::camel($k)] = $v;
        }

        return $vars;
    }
}
