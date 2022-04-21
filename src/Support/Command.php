<?php
namespace BladeCLI\Support;

use BladeCLI\Blade;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command as BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

class Command extends BaseCommand
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->laravel = new Container;

        $this->filesystem = new Filesystem;

        parent::__construct();
    }


    /**
     * Get the blade engine ready for rendering.
     *
     * @param string $filePath
     * @return \BladeCLI\Blade
     */
    protected function blade(string $filePath): Blade
    {
        return new Blade(
            container: $this->laravel,
            filesystem: $this->filesystem,
            renderFilePath: $filePath
        );
    }
}
