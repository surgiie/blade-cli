<?php

namespace Surgiie\BladeCLI\Support;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Surgiie\BladeCLI\Blade;

class Command extends BaseCommand
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $laravel;
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * A cached blade instance to utilize.
     *
     * @var Blade|null
     */
    protected ?Blade $bladeInstance = null;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->laravel = new Container();

        $this->filesystem = new Filesystem();

        parent::__construct();
    }

    /**
     * Return a render ready blade instance for a given file.
     *
     * @param string $filePath
     * @param array $options
     * @return \Surgiie\BladeCLI\Blade
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
