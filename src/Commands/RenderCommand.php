<?php

namespace BladeCLI\Commands;

use BladeCLI\Blade;
use Illuminate\Support\Str;
use BladeCLI\Support\Command;
use Symfony\Component\Finder\Finder;
use BladeCLI\Support\ArgvOptionsParser;
use BladeCLI\Support\Concerns\LoadsJsonFiles;

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
                                   {--use-collections : Convert array options to collection instances.}
                                   {--force : Force render or overwrite files.}";



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

        global $argv;

        $this->ignoreValidationErrors();

        $parser = new ArgvOptionsParser(array_slice($argv, 3));

        foreach ($parser->parse() as $name => $mode) {
            $this->registerDynamicOption($name, $mode);
        }
    }
    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle()
    {
        $file = $this->argument("file");

        if (!file_exists($file)) {
            $this->error("The file or directory '$file' does not exist.");
            return 1;
        }

        $data = $this->getFileVariableData();

        if (is_file($file)) {
            $this->renderFile($file, $data);
        }

        if (is_dir($file)) {
            $this->renderDirectoryFiles($file, $data);
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
    protected function renderFile(string $file, array $data = []): static
    {
        $options = $this->options();

        $blade = $this->blade($file, $options);

        $result = $blade->render(data: $data);

        if($result !== false){
            $file =  $blade->getSaveLocation();

            $this->info("Rendered $file");
        }

        return $this;
    }

    /**
     * Render a directory of files.
     *
     * @param string $directory
     * @param array $data
     * @return static
     */
    protected function renderDirectoryFiles(string $directory, array $data = []): static
    {
        $finder = $this->finder();
        $files = $finder->in($directory)->files();

        foreach ($files as $file) {
            $this->renderFile($file->getPathName(), $data);
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

        foreach($jsonFiles as $file){
            $json = array_merge($json, $this->loadJsonFile($file));
        }

        return array_merge($json, $merge);
    }

    /**
     * Get the data to use for the render file.
     *
     * @return array
     */
    protected function getFileVariableData() : array
    {
        $vars = [];

        foreach($this->getJsonFileData(merge: $this->options()) as $k=>$v){
            if($this->isReservedOption($k)){
                continue;
            }
            $vars[Str::camel($k)] = $v;
        }

        return $vars;
    }

}
