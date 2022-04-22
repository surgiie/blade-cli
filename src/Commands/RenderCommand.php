<?php

namespace BladeCLI\Commands;

use Illuminate\Support\Str;
use BladeCLI\Support\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class RenderCommand extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = "render {file}{--save-to=}";

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
        'save-to',
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

        global $argv;
        // parse options to be used as template/var data.
        foreach (array_slice($argv, 3) as $token) {
            // match for a value option
            preg_match("/--(.*)=(.*)/", $token, $match);

            if ($match) {
                $this->registerDynamicOption(name: $match[1], mode: InputOption::VALUE_REQUIRED);
                continue;
            }
            // otherwise match a boolean option.
            preg_match("/--(.*)/", $token, $match);
            if ($match) {
                $this->registerDynamicOption(name: $match[1], mode: InputOption::VALUE_NONE);
                continue;
            }
            // encountered something that is not --option=value or --option format.
            throw new InvalidArgumentException("Invalid or unaccepted argument/option: $token");
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

        $data = $this->getRenderFileData();

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
        if($this->isReservedOption($name)){
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
     * Get the data to use for the render file.
     *
     * @return void
     */
    protected function getRenderFileData()
    {
        $vars = array_filter($this->options(), function ($k) {
            return !$this->isReservedOption($k);
        }, ARRAY_FILTER_USE_KEY);

        $data = [];

        foreach($vars as $k=>$v){
            $data[Str::camel($k)] = $v;
        }

        return $data;
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

        $file = $this->option('save-to')?:$blade->getDefaultSaveFileLocation(absolute: false);

        $this->info("Rendered $file");

        return 0;
    }
}
