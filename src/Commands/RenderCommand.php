<?php

namespace BladeCLI\Commands;

use SplFileInfo;
use BladeCLI\Blade;
use Illuminate\Support\Str;
use BladeCLI\Support\Command;
use Symfony\Component\Finder\Finder;
use BladeCLI\Support\ArgvOptionsParser;
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
                                   {--use-collections : Convert array options to collection instances.}
                                   {--force : Force render or overwrite files.}";



    /**
     * A flag to determine if we are testing command.
     *
     * @var boolean
     */
    protected static $testing = false;

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
        "use-collections",
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

        $this->ignoreValidationErrors();

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
        // todo check if testing/parse input options already set from calling CommandTester->run
        global $argv;
        $parser = new ArgvOptionsParser(array_slice($argv, 3));

        foreach ($parser->parse() as $name => $mode) {
            $this->registerDynamicOption($name, $mode);
        }

        //rebind input definition
        $input->bind($this->getDefinition());
    }

    /**
     * Set the testing flag.
     *
     * @return void
     */
    public static function testing()
    {
        static::$testing = true;
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle()
    {
        $options = $this->options();
        $file = $this->argument("file");

        if (!file_exists($file)) {
            throw new FileNotFoundException("The file or directory '$file' does not exist.");
        }

        $data = $this->getFileVariableData();

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
     * Updates the save directory for a file being
     * rendered during a directory render so that
     * it gets rendered in a mirrored location to its
     * current directory/location.
     *
     * @param array $options
     * @param string $filePath
     * @param array $options
     * @return array
     */
    protected function getSaveDirForDirectoryFileRender(string $directory, string $filePath, array $options)
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
            $renderOptions = $this->getSaveDirForDirectoryFileRender($directory, $pathName, $options);
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
    protected function registerDynamicOption($name, $mode): bool
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
     * @return array
     */
    public function getJsonFileData($merge = []): array
    {
        $json = [];
        $jsonFiles = $this->option("from-json");

        foreach ($jsonFiles as $file) {
            $json = array_merge($json, $this->loadJsonFile($file));
        }

        return array_merge($json, $merge);
    }

    /**
     * Get the data to use for the render file.
     *
     * @return array
     */
    protected function getFileVariableData(): array
    {
        $vars = [];

        foreach ($this->getJsonFileData(merge: $this->options()) as $k => $v) {
            if ($this->isReservedOption($k)) {
                continue;
            }
            $vars[Str::camel($k)] = $v;
        }

        return $vars;
    }
}
