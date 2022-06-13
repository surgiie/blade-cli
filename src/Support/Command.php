<?php

namespace BladeCLI\Support;

use BladeCLI\Blade;
use BladeCLI\BladeX;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command as BaseCommand;

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
     * @var BladeX|null
     */
    protected ?BladeX $bladeInstance = null;

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
     * @return \BladeCLI\Blade
     */
    protected function blade(string $filePath, array $options = []): BladeX
    {
        if (! is_null($this->bladeInstance)) {
            return $this->bladeInstance->setOptions($options)->setFilePath($filePath);
        }

        return $this->bladeInstance = new BladeX(
            container: $this->laravel,
            filesystem: $this->filesystem,
            filePath: $filePath,
            options: $options
        );
    }
}
