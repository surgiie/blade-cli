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
        $this->info("Rendering...");

        $file = $this->argument('file');

        if(is_file($file)){
            return $this->renderFile($file);
        }

        if(is_dir($file)){
           return $this->renderDirectoryFiles($file);
        }

        if(!file_exists($file)){
            $this->error("File [$file] does not exist.");
            return 1;
        }
    }

    /**
     * Render template file.
     *
     * @return int
     */
    protected function renderFile(string $file): int
    {
        return 0;
    }
}