<?php
namespace BladeCLI\Support;


use BladeCLI\Blade;
use Illuminate\Console\Command as BaseCommand;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
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
     * @return \BladeCLI\Blade
     */
    protected function blade(string $filePath, array $options = []): Blade
    {
        if(!is_null($this->bladeInstance)){
            return $this->bladeInstance->setFilePath($filePath)->setOptions($options);
        }

        return $this->bladeInstance = new Blade(
            container: $this->laravel,
            filesystem: $this->filesystem,
            filePath: $filePath,
            options: $options
        );
    }
}
