<?php

namespace Surgiie\BladeCLI\Support;

use Surgiie\BladeCLI\Blade;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command as BaseCommand;
use Surgiie\BladeCLI\Application;
use Illuminate\Console\View\Components\Factory as ConsoleViewFactory;

class Command extends BaseCommand
{
    /**
     * The container instance.
     */
    protected $laravel;
    /**
     * The filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * A cached blade instance to utilize.
     */
    protected ?Blade $bladeInstance = null;


    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        $this->laravel = new Container();

        Application::setContainerInstance($this->laravel);

        $this->filesystem = new Filesystem();

        parent::__construct();

        $this->components = $this->laravel->make(ConsoleViewFactory::class, ['output' => $this->output]);
    }


    /**
     * Return a render ready blade instance for a given file.
     */
    protected function blade(string $filePath, array $options = []): Blade
    {
        if (! is_null($this->bladeInstance)) {
            return $this->bladeInstance->setOptions($options)->setFilePath($filePath);
        }

        return $this->bladeInstance = new Blade(
            container: $this->laravel,
            filesystem: $this->filesystem,
            filePath: $filePath,
            options: $options
        );
    }
}
