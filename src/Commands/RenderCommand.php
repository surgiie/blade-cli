<?php

namespace BladeCLI\Commands;

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
    protected $signature = 'render {file}';

    /**
     * The command's description.
     *
     * @var string
     */
    protected $description = 'Render a template file.';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->ignoreValidationErrors();

        $this->parseVarOptions();
    }

    /**
     * Register unknown options meant for template/var data.
     *
     * @return void
     */
    protected function parseVarOptions()
    {
        global $argv;
        // parse options to be used as template/var data.
        foreach(array_slice($argv, 3) as $token){
            preg_match('/--(.*)=(.*)/', $token, $match);
            if($match){
                $this->addOption($match[1], mode: InputOption::VALUE_REQUIRED);
            }else{
                throw new InvalidArgumentException("Invalid or unaccepted argument/option: $token");
            }
        }
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $file = $this->argument('file');

        if(!file_exists($file)){
            $this->error("The file or directory '$file' does not exist.");
            return 1;
        }

        if(is_file($file)){
            return $this->renderFile($file);
        }

        if(is_dir($file)){
            return $this->renderDirectoryFiles($file);
        }

        $this->info("Rendered $file.");
    }

    /**
     * Render template file.
     *
     * @return int
     */
    protected function renderFile(string $file): int
    {
        $blade = $this->blade($file);

        $blade->render();

        return 0;
    }
}