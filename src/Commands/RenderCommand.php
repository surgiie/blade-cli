<?php

namespace BladeCLI\Commands;

use Illuminate\Support\Str;
use BladeCLI\Support\Command;
use BladeCLI\Support\CommandOptionsParser;
use BladeCLI\Support\Concerns\LoadsJsonFiles;

class RenderCommand extends Command
{
    use LoadsJsonFiles;

    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = "render {file}{--save-dir=}{--from-json=*}";

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
        "save-dir",
        "from-json",
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

        $parser = new CommandOptionsParser(array_slice($argv, 3));

        foreach ($parser->parse() as $name => $mode) {
            $this->registerDynamicOption($name, $mode);
        }
    }
    /**
     * Execute the command.
     *
     * @return void
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
            return $this->renderFile($file, $data);
        }

        if (is_dir($file)) {
            return $this->renderDirectoryFiles($file, $data);
        }
    }
    /**
     * Register a dynamic option parsed from args.
     *
     * @param string $name
     * @param int $mode
     * @return bool
     */
    protected function registerDynamicOption($name, $mode)
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
    public function getJsonFileData($merge = [])
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
     * @return void
     */
    protected function getFileVariableData()
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

    /**
     * Render template file.
     *
     * @param string $file
     * @param array $data
     * @return int
     */
    protected function renderFile(string $file, array $data = []): int
    {
        $blade = $this->blade($file);

        $blade->render(options: $this->options(), data: $data);

        $file =  $blade->getSaveLocation($this->option("save-dir"));

        $this->info("Rendered $file");

        return 0;
    }
}
