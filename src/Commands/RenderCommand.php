<?php
namespace BladeCLI\Commands;

use BladeCLI\Support\Command;


class RenderCommand extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    protected $signature = 'render';

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
    }
}