<?php

namespace BladeCLI\Commands;

use BladeCLI\Blade;
use BladeCLI\Support\Command;

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